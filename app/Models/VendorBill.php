<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorBill extends Model
{
    protected $fillable = [
        'invoice_id',
        'vendor_name',
        'vendor_bill_number',
        'bill_amount',
        'service_type',
        'service_details',
        'payment_status',
        'payment_date',
        'notes',
    ];

    protected $casts = [
        'bill_amount' => 'decimal:2',
        'payment_date' => 'date',
        'payment_status' => 'string',
    ];

    /**
     * Get the invoice that owns this vendor bill
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the lead through the invoice relationship
     */
    public function lead()
    {
        return $this->hasOneThrough(Lead::class, Invoice::class, 'id', 'id', 'invoice_id', 'lead_id');
    }

    /**
     * Check if vendor bill is paid
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Check if vendor bill is pending payment
     */
    public function isPending(): bool
    {
        return $this->payment_status === 'pending';
    }

    /**
     * Mark vendor bill as paid
     */
    public function markAsPaid(): void
    {
        $this->update([
            'payment_status' => 'paid',
            'payment_date' => now(),
        ]);
        
        // Automatically update invoice vendor payment status
        $this->invoice->updateVendorPaymentStatus();
    }

    /**
     * Mark vendor bill as pending
     */
    public function markAsPending(): void
    {
        $this->update([
            'payment_status' => 'pending',
            'payment_date' => null,
        ]);
        
        // Automatically update invoice vendor payment status
        $this->invoice->updateVendorPaymentStatus();
    }

    /**
     * Boot the model and add event listeners
     */
    protected static function boot()
    {
        parent::boot();

        // Update invoice vendor payment status when vendor bill is created, updated, or deleted
        static::saved(function ($vendorBill) {
            $vendorBill->invoice->updateVendorPaymentStatus();
        });

        static::deleted(function ($vendorBill) {
            $vendorBill->invoice->updateVendorPaymentStatus();
        });
    }
}
