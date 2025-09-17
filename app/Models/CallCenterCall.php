<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallCenterCall extends Model
{
    protected $fillable = [
        'lead_id',
        'assigned_call_center_user',
        'call_type',
        'status',
        'call_notes',
        'call_attempts',
        'last_call_attempt',
        'call_checklist_completed',
    ];

    protected $casts = [
        'call_checklist_completed' => 'array',
        'last_call_attempt' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_call_center_user');
    }

    // Call type constants
    const CALL_TYPE_PRE_DEPARTURE = 'pre_departure';
    const CALL_TYPE_POST_ARRIVAL = 'post_arrival';

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_ASSIGNED = 'assigned';
    const STATUS_CALLED = 'called';
    const STATUS_NOT_ANSWERED = 'not_answered';
    const STATUS_COMPLETED = 'completed';

    public static function getCallTypes(): array
    {
        return [
            self::CALL_TYPE_PRE_DEPARTURE => 'Pre-Departure',
            self::CALL_TYPE_POST_ARRIVAL => 'Post-Arrival',
        ];
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_ASSIGNED => 'Assigned',
            self::STATUS_CALLED => 'Called',
            self::STATUS_NOT_ANSWERED => 'Not Answered',
            self::STATUS_COMPLETED => 'Completed',
        ];
    }

    public function getCallTypeLabel(): string
    {
        return self::getCallTypes()[$this->call_type] ?? $this->call_type;
    }

    public function getStatusLabel(): string
    {
        return self::getStatuses()[$this->status] ?? $this->status;
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'gray',
            self::STATUS_ASSIGNED => 'warning',
            self::STATUS_CALLED => 'info',
            self::STATUS_NOT_ANSWERED => 'danger',
            self::STATUS_COMPLETED => 'success',
            default => 'gray',
        };
    }
}
