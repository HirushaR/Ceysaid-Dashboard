<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\VendorBill;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierOpeningBalanceService
{
    public function sync(Supplier $supplier, float $amount, ?string $date): ?VendorBill
    {
        return DB::transaction(function () use ($supplier, $amount, $date): ?VendorBill {
            $amount = round(max(0, $amount), 2);
            $bill = VendorBill::query()->where('supplier_id', $supplier->id)->where('is_opening_balance', true)->with('vendorBillPayments')->lockForUpdate()->first();

            if ($amount <= 0) {
                if ($bill && $bill->total_paid_amount > 0) {
                    throw ValidationException::withMessages(['opening_balance' => 'The opening balance cannot be removed because payments have already been set off against it.']);
                }
                $bill?->delete();

                return null;
            }

            if ($bill && $amount + 0.009 < $bill->total_paid_amount) {
                throw ValidationException::withMessages(['opening_balance' => 'Opening balance cannot be lower than the amount already set off: LKR '.number_format($bill->total_paid_amount, 2).'.']);
            }

            $values = [
                'supplier_id' => $supplier->id,
                'vendor_name' => $supplier->name,
                'vendor_bill_number' => $bill?->vendor_bill_number ?? 'OB/'.now()->year.'/'.$supplier->id,
                'is_opening_balance' => true,
                'bill_amount' => $amount,
                'due_date' => $date,
                'service_type' => 'OPENING_BALANCE',
                'service_details' => 'Supplier payable opening balance',
                'payment_status' => $bill?->payment_status ?? 'pending',
            ];

            if ($bill) {
                $bill->update($values);
                $bill->recalculateFromPayments();

                return $bill->fresh();
            }

            return VendorBill::create($values);
        });
    }
}
