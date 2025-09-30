<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\LeaveType;
use App\Enums\LeaveStatus;
use Guava\Calendar\Contracts\Eventable;
use Guava\Calendar\ValueObjects\CalendarEvent;

class Leave extends Model implements Eventable
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'description',
        'start_date',
        'end_date',
        'hours',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'created_by',
    ];

    protected $casts = [
        'type' => LeaveType::class,
        'status' => LeaveStatus::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'hours' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getDurationInDaysAttribute(): int
    {
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }
        
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    public function isPending(): bool
    {
        return $this->status === LeaveStatus::PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === LeaveStatus::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === LeaveStatus::REJECTED;
    }

    /**
     * Convert the model to a calendar event
     */
    public function toCalendarEvent(): CalendarEvent
    {
        return CalendarEvent::make()
            ->title($this->getCalendarEventTitle())
            ->start($this->start_date)
            ->end($this->end_date)
            ->backgroundColor($this->getCalendarEventBackgroundColor())
            ->textColor($this->getCalendarEventTextColor())
            ->allDay(true)
            ->extendedProps([
                'leave_id' => $this->id,
                'user_name' => $this->user->name ?? 'Unknown',
                'type' => $this->type->getLabel(),
                'status' => $this->status->getLabel(),
                'description' => $this->description,
                'hours' => $this->hours,
                'approved_by' => $this->approver?->name,
                'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
            ]);
    }

    /**
     * Get the calendar event title
     */
    protected function getCalendarEventTitle(): string
    {
        $userName = $this->user->name ?? 'Unknown';
        $type = $this->type->getLabel();
        $status = $this->status->getLabel();
        
        return "{$userName} - {$type} ({$status})";
    }

    /**
     * Get the calendar event background color based on status
     */
    protected function getCalendarEventBackgroundColor(): string
    {
        return match ($this->status) {
            LeaveStatus::APPROVED => '#10b981', // green
            LeaveStatus::PENDING => '#f59e0b', // yellow
            LeaveStatus::REJECTED => '#ef4444', // red
            LeaveStatus::CANCELLED => '#6b7280', // gray
        };
    }

    /**
     * Get the calendar event text color
     */
    protected function getCalendarEventTextColor(): string
    {
        return '#ffffff';
    }
}
