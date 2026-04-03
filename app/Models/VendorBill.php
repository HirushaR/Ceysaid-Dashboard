<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorBill extends Model
{
    protected $fillable = [
        'invoice_id',
        'supplier_id',
        'vendor_name',
        'vendor_bill_number',
        'bill_amount',
        'due_date',
        'service_type',
        'service_details',
        'payment_status',
        'payment_date',
        'payment_mode',
        'paid_through',
        'notes',
    ];

    protected $casts = [
        'bill_amount' => 'decimal:2',
        'due_date' => 'date',
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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
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
    public function markAsPaid(null|string|DateTimeInterface $paymentDate = null, ?string $paymentMode = null, ?string $paidThrough = null): void
    {
        $date = $paymentDate instanceof DateTimeInterface
            ? $paymentDate
            : ($paymentDate !== null && $paymentDate !== '' ? Carbon::parse($paymentDate) : now());

        $this->update([
            'payment_status' => 'paid',
            'payment_date' => $date,
            'payment_mode' => $paymentMode,
            'paid_through' => $paidThrough,
        ]);

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
            'payment_mode' => null,
            'paid_through' => null,
        ]);

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
