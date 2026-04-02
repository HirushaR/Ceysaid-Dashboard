<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLineItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'lead_cost_id',
        'sort_order',
        'description',
        'customer_details',
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
        static::saving(function (InvoiceLineItem $item) {
            $item->amount = (string) round((float) $item->quantity * (float) $item->rate, 2);
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function leadCost(): BelongsTo
    {
        return $this->belongsTo(LeadCost::class);
    }
}
