<?php

namespace App\Models;

use App\Enums\AirTicketStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AirTicketRequest extends Model
{
    protected $fillable = [
        'vendor_bill_id',
        'provider',
        'booking_reference',
        'airline',
        'time_limit',
        'amount',
        'status',
        'queued_by',
        'queued_at',
        'approved_by',
        'approved_at',
        'decision_notes',
        'issued_by',
        'issued_at',
        'ticket_number',
        'issue_notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'status' => AirTicketStatus::class,
        'time_limit' => 'datetime',
        'queued_at' => 'datetime',
        'approved_at' => 'datetime',
        'issued_at' => 'datetime',
    ];

    public function vendorBill(): BelongsTo
    {
        return $this->belongsTo(VendorBill::class);
    }

    public function queuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'queued_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function isTimeLimitExpired(): bool
    {
        return in_array($this->status, [
            AirTicketStatus::PENDING_APPROVAL,
            AirTicketStatus::READY_FOR_TICKETING,
            AirTicketStatus::ON_HOLD,
        ], true) && ($this->time_limit?->isPast() ?? false);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin() || $user->isAccount() || ($user->isSales() && $user->isManager()) || $user->hasPermission('air_tickets.view')) {
            return $query;
        }

        if ($user->isSales()) {
            return $query->where(fn (Builder $visible) => $visible
                ->where('queued_by', $user->id)
                ->orWhereHas('vendorBill.invoice.lead', fn (Builder $lead) => $lead->where('assigned_to', $user->id)));
        }

        if ($user->isOperation()) {
            return $query->where(fn (Builder $visible) => $visible
                ->where('queued_by', $user->id)
                ->orWhereHas('vendorBill.invoice.lead', fn (Builder $lead) => $lead->where('assigned_operator', $user->id)));
        }

        return $query->whereRaw('1 = 0');
    }
}
