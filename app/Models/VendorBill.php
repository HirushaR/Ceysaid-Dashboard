<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return HasMany<VendorBillPayment, $this>
     */
    public function vendorBillPayments(): HasMany
    {
        return $this->hasMany(VendorBillPayment::class)->orderByDesc('payment_date')->orderByDesc('id');
    }

    /**
     * @return HasMany<VendorBillLineItem, $this>
     */
    public function lineItems(): HasMany
    {
        return $this->hasMany(VendorBillLineItem::class)->orderBy('sort_order');
    }

    /** Set bill_amount from line item rows and refresh payment status (when lines exist). */
    public function syncBillAmountFromLineItems(): void
    {
        if (! $this->exists) {
            return;
        }

        if ($this->lineItems()->count() === 0) {
            return;
        }

        $sum = round((float) $this->lineItems()->sum('amount'), 2);

        if (abs((float) $this->bill_amount - $sum) < 0.0001) {
            $this->refresh();
            $this->recalculateFromPayments();

            return;
        }

        $this->update(['bill_amount' => $sum]);
        $this->refresh();
        $this->recalculateFromPayments();
    }

    public function lead()
    {
        return $this->hasOneThrough(Lead::class, Invoice::class, 'id', 'id', 'invoice_id', 'lead_id');
    }

    public function getTotalPaidAmountAttribute(): float
    {
        if ($this->relationLoaded('vendorBillPayments')) {
            return round((float) $this->vendorBillPayments->sum('amount'), 2);
        }

        return round((float) $this->vendorBillPayments()->sum('amount'), 2);
    }

    /** Remaining vendor bill balance (bill amount minus payments recorded). */
    public function getOutstandingAmountAttribute(): float
    {
        return round(max(0, (float) $this->bill_amount - $this->total_paid_amount), 2);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isPartial(): bool
    {
        return $this->payment_status === 'partial';
    }

    public function isPending(): bool
    {
        return $this->payment_status === 'pending';
    }

    /**
     * Sync aggregate payment fields from vendor_bill_payments rows and refresh invoice vendor status.
     */
    public function recalculateFromPayments(): void
    {
        $total = (float) $this->vendorBillPayments()->sum('amount');
        $bill = (float) $this->bill_amount;
        $last = $this->vendorBillPayments()->orderByDesc('payment_date')->orderByDesc('id')->first();

        if ($total <= 0) {
            $this->update([
                'payment_status' => 'pending',
                'payment_date' => null,
                'payment_mode' => null,
                'paid_through' => null,
            ]);

            return;
        }

        if ($total + 0.009 >= $bill) {
            $status = 'paid';
        } else {
            $status = 'partial';
        }

        $this->update([
            'payment_status' => $status,
            'payment_date' => $last?->payment_date,
            'payment_mode' => $last?->payment_mode,
            'paid_through' => $last?->paid_through,
        ]);
    }

    /**
     * Record a single payment for the remaining balance (legacy / quick-settle).
     */
    public function markAsPaid(null|string|DateTimeInterface $paymentDate = null, ?string $paymentMode = null, ?string $paidThrough = null): void
    {
        $this->load('vendorBillPayments');
        $remaining = $this->outstanding_amount;
        if ($remaining <= 0) {
            return;
        }

        $date = $paymentDate instanceof DateTimeInterface
            ? $paymentDate
            : ($paymentDate !== null && $paymentDate !== '' ? Carbon::parse($paymentDate) : now());

        $this->vendorBillPayments()->create([
            'amount' => $remaining,
            'payment_date' => $date,
            'payment_mode' => $paymentMode ?? 'bank_transfer',
            'paid_through' => $paidThrough ?? 'cash',
            'notes' => null,
        ]);
    }

    /** Remove all payments (bill returns to unpaid). */
    public function markAsPending(): void
    {
        $this->vendorBillPayments()->delete();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saved(function (VendorBill $vendorBill): void {
            $vendorBill->invoice?->updateVendorPaymentStatus();
        });

        static::deleted(function (VendorBill $vendorBill): void {
            $vendorBill->invoice?->updateVendorPaymentStatus();
        });
    }
}
