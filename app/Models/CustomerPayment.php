<?php

namespace App\Models;

use App\Services\DocumentNumberService;
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
        'deposit_to',
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

        static::creating(function (CustomerPayment $payment) {
            if (filled($payment->receipt_number)) {
                return;
            }
            if (! $payment->invoice_id) {
                return;
            }
            $invoice = Invoice::query()->find($payment->invoice_id);
            if (! $invoice?->lead_id) {
                return;
            }
            $payment->receipt_number = app(DocumentNumberService::class)
                ->nextCustomerReceiptNumberForLead((int) $invoice->lead_id);
        });

        // Update invoice payment status when payment is created, updated, or deleted
        static::saved(function ($payment) {
            $payment->invoice->updateCustomerPaymentStatus();
        });

        static::deleted(function ($payment) {
            $payment->invoice->updateCustomerPaymentStatus();
        });
    }
}
