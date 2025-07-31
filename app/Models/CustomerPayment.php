<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPayment extends Model
{
    protected $fillable = [
        'invoice_id',
        'amount',
        'payment_date',
        'receipt_number',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    /**
     * Get the invoice that owns this payment
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Boot the model and add event listeners
     */
    protected static function boot()
    {
        parent::boot();

        // Update invoice payment status when payment is created, updated, or deleted
        static::saved(function ($payment) {
            $payment->invoice->updateCustomerPaymentStatus();
        });

        static::deleted(function ($payment) {
            $payment->invoice->updateCustomerPaymentStatus();
        });
    }
}