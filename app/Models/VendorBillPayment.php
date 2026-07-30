<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorBillPayment extends Model
{
    protected $fillable = [
        'vendor_bill_id',
        'supplier_payment_id',
        'amount',
        'payment_date',
        'payment_mode',
        'paid_through',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function vendorBill(): BelongsTo
    {
        return $this->belongsTo(VendorBill::class);
    }

    public function supplierPayment(): BelongsTo
    {
        return $this->belongsTo(SupplierPayment::class);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saved(function (VendorBillPayment $payment): void {
            $payment->vendorBill->recalculateFromPayments();
        });

        static::deleted(function (VendorBillPayment $payment): void {
            $bill = VendorBill::query()->find($payment->vendor_bill_id);
            if ($bill) {
                $bill->recalculateFromPayments();
            }
        });
    }
}
