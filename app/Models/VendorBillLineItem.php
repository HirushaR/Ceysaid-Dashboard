<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorBillLineItem extends Model
{
    /** @param  array<int, mixed>  $rows  Repeater state (description, quantity, rate) */
    public static function sumAmountsFromFormArray(array $rows): float
    {
        $sum = 0.0;
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $sum += round((float) ($row['quantity'] ?? 0) * (float) ($row['rate'] ?? 0), 2);
        }

        return round($sum, 2);
    }

    protected $fillable = [
        'vendor_bill_id',
        'sort_order',
        'description',
        'quantity',
        'rate',
        'amount',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'rate' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (VendorBillLineItem $item) {
            $item->amount = (string) round((float) $item->quantity * (float) $item->rate, 2);
        });

        static::saved(function (VendorBillLineItem $item) {
            $item->vendorBill->syncBillAmountFromLineItems();
        });

        static::deleted(function (VendorBillLineItem $item) {
            $bill = VendorBill::query()->find($item->vendor_bill_id);
            $bill?->syncBillAmountFromLineItems();
        });
    }

    public function vendorBill(): BelongsTo
    {
        return $this->belongsTo(VendorBill::class);
    }
}
