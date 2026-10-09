<?php

namespace App\Services;

use App\Models\SupplierPayment;
use App\Models\VendorBill;
use App\Models\VendorBillPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateSupplierPaymentService
{
    public function update(SupplierPayment $payment, array $data): SupplierPayment
    {
        return DB::transaction(function () use ($payment, $data): SupplierPayment {
            $payment = SupplierPayment::query()->lockForUpdate()->findOrFail($payment->id);
            $oldValues = $payment->toArray();
            $oldBillIds = $payment->allocations()->pluck('vendor_bill_id')->all();
            $amount = round((float) $data['amount'], 2);
            $prepared = [];
            $total = 0.0;
            $seen = [];

            foreach (array_values($data['allocations'] ?? []) as $index => $allocation) {
                $billId = (int) ($allocation['vendor_bill_id'] ?? 0);
                $allocated = round((float) ($allocation['amount'] ?? 0), 2);
                if ($billId <= 0 || isset($seen[$billId])) {
                    throw ValidationException::withMessages(["allocations.{$index}.vendor_bill_id" => 'Select each vendor bill only once.']);
                }
                $seen[$billId] = true;
                $bill = VendorBill::query()->whereKey($billId)->lockForUpdate()->first();
                if (! $bill || (int) $bill->supplier_id !== (int) $payment->supplier_id) {
                    throw ValidationException::withMessages(["allocations.{$index}.vendor_bill_id" => 'The selected bill does not belong to this supplier.']);
                }
                $paidByOthers = (float) VendorBillPayment::query()->where('vendor_bill_id', $billId)->where(function ($q) use ($payment): void {
                    $q->whereNull('supplier_payment_id')->orWhere('supplier_payment_id', '<>', $payment->id);
                })->sum('amount');
                $available = round(max(0, (float) $bill->bill_amount - $paidByOthers), 2);
                if ($allocated <= 0 || $allocated > $available + 0.009) {
                    throw ValidationException::withMessages(["allocations.{$index}.amount" => 'Allocation must be positive and cannot exceed LKR '.number_format($available, 2).'.']);
                }
                $total = round($total + $allocated, 2);
                $prepared[] = ['bill' => $bill, 'amount' => $allocated];
            }
            if ($prepared !== [] && abs($total - $amount) > 0.009) {
                throw ValidationException::withMessages(['amount' => 'Payment amount must equal the allocated total of LKR '.number_format($total, 2).'.']);
            }

            $payment->update([
                'payment_date' => Carbon::parse($data['payment_date'])->toDateString(), 'amount' => $amount,
                'payment_mode' => $data['payment_mode'], 'paid_through' => $data['paid_through'],
                'reference_number' => $data['reference_number'] ?: null, 'notes' => $data['notes'] ?: null,
            ]);
            $payment->allocations()->get()->each->delete();
            foreach ($prepared as $allocation) {
                $payment->allocations()->create(['vendor_bill_id' => $allocation['bill']->id, 'amount' => $allocation['amount'], 'payment_date' => $payment->payment_date, 'payment_mode' => $payment->payment_mode, 'paid_through' => $payment->paid_through, 'notes' => $payment->notes]);
            }
            VendorBill::query()->whereIn('id', array_unique(array_merge($oldBillIds, array_column(array_map(fn ($row) => ['id' => $row['bill']->id], $prepared), 'id'))))->get()->each->recalculateFromPayments();
            app(AuditService::class)->record('SupplierPayment.edited', $payment, $oldValues, $payment->fresh()->toArray(), $data['audit_reason']);

            return $payment->fresh(['supplier', 'allocations.vendorBill.invoice.lead', 'creator']);
        });
    }
}
