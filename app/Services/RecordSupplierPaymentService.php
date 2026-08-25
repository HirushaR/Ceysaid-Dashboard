<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\VendorBill;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordSupplierPaymentService
{
    public function __construct(
        private DocumentNumberService $numbers
    ) {}

    /**
     * @param  array{
     *     supplier_id: int,
     *     payment_date: string,
     *     amount: numeric-string|int|float,
     *     payment_mode: string,
     *     paid_through: string,
     *     reference_number?: string|null,
     *     notes?: string|null,
     *     allocations?: array<int, array{vendor_bill_id: int, amount: numeric-string|int|float}>
     * }  $data
     */
    public function record(array $data): SupplierPayment
    {
        return DB::transaction(function () use ($data): SupplierPayment {
            $supplier = Supplier::query()->find($data['supplier_id'] ?? null);
            if (! $supplier) {
                throw ValidationException::withMessages([
                    'supplier_id' => 'Please select a valid supplier.',
                ]);
            }

            $paymentDate = Carbon::parse($data['payment_date'] ?? null)->startOfDay();
            if ($paymentDate->isAfter(now()->startOfDay())) {
                throw ValidationException::withMessages([
                    'payment_date' => 'The payment date cannot be in the future.',
                ]);
            }

            $paymentAmount = round((float) ($data['amount'] ?? 0), 2);
            if ($paymentAmount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment amount must be greater than zero.',
                ]);
            }

            $allocations = array_values($data['allocations'] ?? []);
            $prepared = [];
            $allocatedTotal = 0.0;
            $seenBillIds = [];

            foreach ($allocations as $index => $allocation) {
                $billId = (int) ($allocation['vendor_bill_id'] ?? 0);
                $amount = round((float) ($allocation['amount'] ?? 0), 2);

                if ($billId <= 0 || isset($seenBillIds[$billId])) {
                    throw ValidationException::withMessages([
                        "allocations.{$index}.vendor_bill_id" => 'Select each vendor bill only once.',
                    ]);
                }
                $seenBillIds[$billId] = true;

                /** @var VendorBill|null $bill */
                $bill = VendorBill::query()
                    ->whereKey($billId)
                    ->lockForUpdate()
                    ->first();

                if (! $bill || (int) $bill->supplier_id !== (int) $supplier->id) {
                    throw ValidationException::withMessages([
                        "allocations.{$index}.vendor_bill_id" => 'The selected bill does not belong to this supplier.',
                    ]);
                }

                if ($amount <= 0) {
                    throw ValidationException::withMessages([
                        "allocations.{$index}.amount" => 'Allocation amount must be greater than zero.',
                    ]);
                }

                $outstanding = $bill->outstanding_amount;
                if ($amount > $outstanding + 0.0001) {
                    throw ValidationException::withMessages([
                        "allocations.{$index}.amount" => 'Allocation cannot exceed the outstanding balance of LKR '.number_format($outstanding, 2).'.',
                    ]);
                }

                $allocatedTotal = round($allocatedTotal + $amount, 2);
                $prepared[] = ['bill' => $bill, 'amount' => $amount];
            }

            if ($allocatedTotal > $paymentAmount + 0.009) {
                throw ValidationException::withMessages([
                    'amount' => 'Bill allocations cannot exceed the payment amount of LKR '.number_format($paymentAmount, 2).'.',
                ]);
            }

            $payment = SupplierPayment::create([
                'supplier_id' => $supplier->id,
                'payment_number' => $this->numbers->nextSupplierPaymentNumber(),
                'payment_date' => $paymentDate->toDateString(),
                'amount' => $paymentAmount,
                'payment_mode' => $data['payment_mode'],
                'paid_through' => $data['paid_through'],
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($prepared as $allocation) {
                $payment->allocations()->create([
                    'vendor_bill_id' => $allocation['bill']->id,
                    'amount' => $allocation['amount'],
                    'payment_date' => $payment->payment_date,
                    'payment_mode' => $payment->payment_mode,
                    'paid_through' => $payment->paid_through,
                    'notes' => $payment->notes,
                ]);
            }

            return $payment->fresh(['supplier', 'allocations.vendorBill.invoice.lead', 'creator']);
        });
    }
}
