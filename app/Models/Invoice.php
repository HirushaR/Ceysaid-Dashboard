<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'lead_id',
        'invoice_number',
        'total_amount',
        'payment_amount',
        'balance_amount',
        'payment_date',
        'receipt_number',
        'description',
        'customer_payment_status',
        'vendor_payment_status',
        'notes',
    ];

    /**
     * Boot the model and add event listeners
     */
    protected static function boot()
    {
        parent::boot();

        // Initialize customer payment status when invoice is created
        static::creating(function ($invoice) {
            if (!$invoice->customer_payment_status) {
                $invoice->customer_payment_status = 'pending';
                $invoice->balance_amount = $invoice->total_amount;
            }
        });

        // Update vendor payment status when invoice is created
        static::created(function ($invoice) {
            $invoice->updateVendorPaymentStatus();
        });
    }

    protected $casts = [
        'total_amount' => 'decimal:2',
        'payment_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'payment_date' => 'date',
        'customer_payment_status' => 'string',
        'vendor_payment_status' => 'string',
    ];

    /**
     * Get the lead that owns this invoice
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Get all vendor bills for this invoice
     */
    public function vendorBills(): HasMany
    {
        return $this->hasMany(VendorBill::class);
    }

    /**
     * Get all customer payments for this invoice
     */
    public function customerPayments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class);
    }

    /**
     * Get the total vendor bills amount
     */
    public function getTotalVendorBillsAmountAttribute(): float
    {
        return $this->vendorBills->sum('bill_amount');
    }

    /**
     * Get the total customer payments amount
     */
    public function getTotalCustomerPaymentsAmountAttribute(): float
    {
        return $this->customerPayments->sum('amount');
    }

    /**
     * Get the remaining balance amount (total - payments received)
     */
    public function getCustomerBalanceAmountAttribute(): float
    {
        return $this->total_amount - $this->total_customer_payments_amount;
    }

    /**
     * Get the profit (total_amount - total vendor bills)
     */
    public function getProfitAttribute(): float
    {
        return $this->total_amount - $this->total_vendor_bills_amount;
    }

    /**
     * Get the profit margin percentage
     */
    public function getProfitMarginAttribute(): float
    {
        if ($this->total_amount == 0) {
            return 0;
        }
        return ($this->profit / $this->total_amount) * 100;
    }

    /**
     * Check if customer payment is fully paid
     */
    public function isCustomerPaid(): bool
    {
        return $this->customer_payment_status === 'paid';
    }

    /**
     * Check if customer payment is partially paid
     */
    public function isCustomerPartiallyPaid(): bool
    {
        return $this->customer_payment_status === 'partial';
    }

    /**
     * Check if customer payment is pending
     */
    public function isCustomerPending(): bool
    {
        return $this->customer_payment_status === 'pending';
    }

    /**
     * Check if all vendor bills are paid
     */
    public function isVendorPaid(): bool
    {
        return $this->vendor_payment_status === 'paid';
    }

    /**
     * Check if some vendor bills are paid
     */
    public function isVendorPartiallyPaid(): bool
    {
        return $this->vendor_payment_status === 'partial';
    }

    /**
     * Check if no vendor bills are paid
     */
    public function isVendorPending(): bool
    {
        return $this->vendor_payment_status === 'pending';
    }

    /**
     * Legacy method - check if invoice is fully paid (customer payment)
     * @deprecated Use isCustomerPaid() instead
     */
    public function isPaid(): bool
    {
        return $this->isCustomerPaid();
    }

    /**
     * Legacy method - check if invoice is partially paid (customer payment)
     * @deprecated Use isCustomerPartiallyPaid() instead
     */
    public function isPartiallyPaid(): bool
    {
        return $this->isCustomerPartiallyPaid();
    }

    /**
     * Legacy method - check if invoice is pending payment (customer payment)
     * @deprecated Use isCustomerPending() instead
     */
    public function isPending(): bool
    {
        return $this->isCustomerPending();
    }

    /**
     * Update customer payment status and balance based on total payments received
     */
    public function updateCustomerPaymentStatus(): void
    {
        $totalPaid = $this->total_customer_payments_amount;
        $balance = $this->total_amount - $totalPaid;
        
        if ($totalPaid >= $this->total_amount) {
            $status = 'paid';
            $balance = 0;
        } elseif ($totalPaid > 0) {
            $status = 'partial';
        } else {
            $status = 'pending';
            $balance = $this->total_amount;
        }

        $this->update([
            'customer_payment_status' => $status,
            'balance_amount' => $balance,
            // Update legacy payment_amount for backwards compatibility
            'payment_amount' => $totalPaid > 0 ? $totalPaid : null,
            'payment_date' => $status === 'paid' ? $this->customerPayments->sortByDesc('payment_date')->first()?->payment_date : null,
        ]);
    }

    /**
     * Update vendor payment status based on vendor bill payments
     */
    public function updateVendorPaymentStatus(): void
    {
        $vendorBills = $this->vendorBills;
        
        if ($vendorBills->isEmpty()) {
            // No vendor bills, mark as paid (nothing to pay)
            $this->update(['vendor_payment_status' => 'paid']);
            return;
        }

        $allPaid = $vendorBills->every(fn($bill) => $bill->payment_status === 'paid');
        $anyPaid = $vendorBills->some(fn($bill) => $bill->payment_status === 'paid');

        if ($allPaid) {
            $this->update(['vendor_payment_status' => 'paid']);
        } elseif ($anyPaid) {
            $this->update(['vendor_payment_status' => 'partial']);
        } else {
            $this->update(['vendor_payment_status' => 'pending']);
        }
    }

    /**
     * Legacy method - Update invoice status based on vendor bill payments
     * @deprecated Use updateVendorPaymentStatus() instead
     */
    public function updatePaymentStatusBasedOnVendorBills(): void
    {
        $this->updateVendorPaymentStatus();
    }
}
