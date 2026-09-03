<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    /** @use HasFactory<\Database\Factories\InvoiceFactory> */
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'tour_id',
        'quote_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'terms',
        'subject',
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
            if (! $invoice->customer_payment_status) {
                $invoice->customer_payment_status = 'pending';
                $invoice->balance_amount = $invoice->total_amount;
            }

            if (! $invoice->tour_id && $invoice->lead_id) {
                $lead = Lead::query()->find($invoice->lead_id);
                if ($lead?->tour_id) {
                    $invoice->tour_id = $lead->tour_id;
                }
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
        'invoice_date' => 'date',
        'due_date' => 'date',
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

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /**
     * @return HasMany<InvoiceLineItem, $this>
     */
    public function lineItems(): HasMany
    {
        return $this->hasMany(InvoiceLineItem::class)->orderBy('sort_order');
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
     *
     * @deprecated Use isCustomerPaid() instead
     */
    public function isPaid(): bool
    {
        return $this->isCustomerPaid();
    }

    /**
     * Legacy method - check if invoice is partially paid (customer payment)
     *
     * @deprecated Use isCustomerPartiallyPaid() instead
     */
    public function isPartiallyPaid(): bool
    {
        return $this->isCustomerPartiallyPaid();
    }

    /**
     * Legacy method - check if invoice is pending payment (customer payment)
     *
     * @deprecated Use isCustomerPending() instead
     */
    public function isPending(): bool
    {
        return $this->isCustomerPending();
    }

    /**
     * Replace all line items from submitted form state.
     *
     * @param  array<int, array<string, mixed>>  $lines
     */
    public function replaceLineItemsFromFormState(array $lines): void
    {
        $this->lineItems()->delete();

        foreach (array_values($lines) as $index => $line) {
            if (! is_array($line)) {
                continue;
            }

            $this->lineItems()->create([
                'lead_cost_id' => $line['lead_cost_id'] ?? null,
                'sort_order' => $index,
                'description' => (string) ($line['description'] ?? ''),
                'customer_details' => $line['customer_details'] ?? null,
                'quantity' => $line['quantity'] ?? 1,
                'rate' => $line['rate'] ?? 0,
            ]);
        }
    }

    /**
     * Set total_amount from line items and refresh customer payment balances.
     */
    public function recalculateTotalsFromLineItems(): void
    {
        $this->load('lineItems');
        $total = round((float) $this->lineItems->sum('amount'), 2);
        $this->total_amount = $total;
        $this->save();
        $this->updateCustomerPaymentStatus();
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

        $allPaid = $vendorBills->every(fn ($bill) => $bill->payment_status === 'paid');
        $anyProgress = $vendorBills->some(fn ($bill) => in_array($bill->payment_status, ['paid', 'partial'], true));

        if ($allPaid) {
            $this->update(['vendor_payment_status' => 'paid']);
        } elseif ($anyProgress) {
            $this->update(['vendor_payment_status' => 'partial']);
        } else {
            $this->update(['vendor_payment_status' => 'pending']);
        }
    }

    /**
     * Legacy method - Update invoice status based on vendor bill payments
     *
     * @deprecated Use updateVendorPaymentStatus() instead
     */
    public function updatePaymentStatusBasedOnVendorBills(): void
    {
        $this->updateVendorPaymentStatus();
    }

    // Analytics Scopes
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopePaid($query)
    {
        return $query->where('customer_payment_status', 'paid');
    }

    public function scopePending($query)
    {
        return $query->where('customer_payment_status', 'pending');
    }

    public function scopePartial($query)
    {
        return $query->where('customer_payment_status', 'partial');
    }

    public function scopeBySalesUser($query, $userId)
    {
        return $query->whereHas('lead', function ($q) use ($userId) {
            $q->where('assigned_to', $userId);
        });
    }

    public function scopeWithProfit($query)
    {
        return $query->whereHas('vendorBills');
    }

    public function scopeByLeadSource($query, $source)
    {
        return $query->whereHas('lead', function ($q) use ($source) {
            $q->where('platform', $source);
        });
    }

    public function scopeByDestination($query, $destination)
    {
        return $query->whereHas('lead', function ($q) use ($destination) {
            $q->where('destination', $destination);
        });
    }

    /**
     * Limit invoices to those the user may access (sales/operation: assigned lead only).
     */
    public function scopeVisibleToUser(Builder $query, User $user): Builder
    {
        if ($user->canViewAllInvoices()) {
            return $query;
        }

        if ($user->isSales() || $user->isOperation()) {
            return $query->whereHas('lead', function (Builder $q) use ($user) {
                if ($user->isSales()) {
                    $q->where('assigned_to', $user->id);
                } else {
                    $q->where('assigned_operator', $user->id);
                }
            });
        }

        return $query;
    }
}
