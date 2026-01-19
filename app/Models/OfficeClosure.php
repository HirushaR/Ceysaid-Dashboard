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
        // Get raw date values from database - no timezone conversion
        // Create Carbon instances in UTC timezone to prevent any timezone conversion
        $rawStart = $this->getRawOriginal('start_date') ?? ($this->start_date ? $this->start_date->toDateString() : null);
        $rawEnd = $this->getRawOriginal('end_date') ?? ($this->end_date ? $this->end_date->toDateString() : null);
        
        $startDate = null;
        $endDate = null;
        
        if ($rawStart) {
            // Create Carbon instance in UTC at midnight - this prevents timezone conversion
            // UTC is timezone-neutral for date-only values
            $startDate = \Carbon\Carbon::createFromFormat('Y-m-d', $rawStart, 'UTC')
                ->setTime(0, 0, 0);
        }
        
        if ($rawEnd) {
            // For all-day events, end date should be exclusive (next day)
            // Create in UTC to prevent timezone conversion
            $endDate = \Carbon\Carbon::createFromFormat('Y-m-d', $rawEnd, 'UTC')
                ->addDay()
                ->setTime(0, 0, 0);
        }
        
        return CalendarEvent::make()
            ->title($this->getCalendarEventTitle())
            ->start($startDate)
            ->end($endDate)
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




