<?php

namespace App\Models;

use App\Enums\QuoteStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Quote extends Model
{
    protected $fillable = [
        'lead_id',
        'family_id',
        'revision',
        'quote_number',
        'status',
        'quote_date',
        'valid_until',
        'terms',
        'subject',
        'notes',
        'sent_at',
        'accepted_at',
        'rejected_at',
        'expired_at',
        'created_by',
    ];

    protected $casts = [
        'quote_date' => 'date',
        'valid_until' => 'date',
        'status' => QuoteStatus::class,
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(QuoteLineItem::class)->orderBy('sort_order');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function actionLogs(): MorphMany
    {
        return $this->morphMany(FinanceActionLog::class, 'subject')->latest();
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(self::class, 'family_id', 'family_id')->orderByDesc('revision');
    }

    public function isEditable(): bool
    {
        return $this->status === QuoteStatus::Draft
            && ! static::query()->where('family_id', $this->family_id)->where('revision', '>', $this->revision)->exists();
    }

    public function isConverted(): bool
    {
        return $this->status === QuoteStatus::Converted;
    }

    /**
     * Limit quotes to those the user may access (sales/operation: assigned lead only).
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

    public function totalAmount(): float
    {
        return (float) $this->lineItems->sum('amount');
    }

    /**
     * State to merge into the invoice create form for a lead (one quote per lead).
     * Draft quotes set quote_id; converted quotes prefill lines only.
     *
     * @return array<string, mixed>
     */
    public static function invoiceFormStateForLeadId(int $leadId): array
    {
        $quote = static::query()
            ->where('lead_id', $leadId)
            ->with(['lineItems' => fn ($q) => $q->orderBy('sort_order')])
            ->first();

        $blank = [
            'quote_id' => null,
            'subject' => null,
            'terms' => 'Due on Receipt',
            'notes' => null,
            'due_date' => now()->format('Y-m-d'),
            'lineItems' => [
                ['description' => '', 'quantity' => 1, 'rate' => 0, 'customer_details' => null],
            ],
        ];

        if (! $quote) {
            return $blank;
        }

        $data = $quote->attributesForInvoiceForm();
        $data['quote_id'] = $quote->isConverted() ? null : $quote->id;

        return $data;
    }

    /**
     * Map quote data into Filament invoice create form state (line items + common fields).
     *
     * @return array<string, mixed>
     */
    public function attributesForInvoiceForm(): array
    {
        $this->loadMissing(['lineItems' => fn ($q) => $q->orderBy('sort_order')]);

        $lines = $this->lineItems->map(fn ($l) => [
            'description' => $l->description,
            'quantity' => $l->quantity,
            'rate' => $l->rate,
            'customer_details' => null,
        ])->values()->all();

        if ($lines === []) {
            $lines = [
                ['description' => '', 'quantity' => 1, 'rate' => 0, 'customer_details' => null],
            ];
        }

        return [
            'subject' => $this->subject,
            'terms' => $this->terms ?? 'Due on Receipt',
            'notes' => $this->notes,
            'due_date' => $this->valid_until?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'lineItems' => $lines,
        ];
    }
}
