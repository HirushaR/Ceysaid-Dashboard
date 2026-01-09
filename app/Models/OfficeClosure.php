<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\ClosureType;
use Guava\Calendar\Contracts\Eventable;
use Guava\Calendar\ValueObjects\CalendarEvent;

class OfficeClosure extends Model implements Eventable
{
    use HasFactory;

    protected $fillable = [
        'title',
        'type',
        'description',
        'start_date',
        'end_date',
        'created_by',
    ];

    protected $casts = [
        'type' => ClosureType::class,
        'start_date' => 'date',
        'end_date' => 'date',
    ];

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
                'closure_id' => $this->id,
                'type' => $this->type->getLabel(),
                'description' => $this->description,
                'created_by' => $this->creator?->name,
            ]);
    }

    /**
     * Get the calendar event title
     */
    protected function getCalendarEventTitle(): string
    {
        return $this->title;
    }

    /**
     * Get the calendar event background color based on type
     */
    protected function getCalendarEventBackgroundColor(): string
    {
        return match ($this->type) {
            ClosureType::HOLIDAY => '#8b5cf6', // purple
            ClosureType::OFFICE_CLOSURE => '#dc2626', // red
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




