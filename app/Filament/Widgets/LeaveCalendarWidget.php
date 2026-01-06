<?php

namespace App\Filament\Widgets;

use App\Models\Leave;
use App\Models\OfficeClosure;
use Guava\Calendar\Widgets\CalendarWidget;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Auth;

class LeaveCalendarWidget extends CalendarWidget
{
    protected string|\Closure|\Illuminate\Support\HtmlString|null $heading = 'Staff Leave & Office Closures Calendar';
    
    protected static ?int $sort = 1;
    
    protected string $calendarView = 'dayGridMonth';

    public static function canView(): bool
    {
        return Auth::user()?->isAdmin() ?? false;
    }

    public function getEvents(array $fetchInfo = []): Collection|array
    {
        $start = $fetchInfo['start'] ?? now()->startOfMonth();
        $end = $fetchInfo['end'] ?? now()->endOfMonth();
        
        // Get leaves
        $leaves = Leave::query()
            ->with(['user', 'approver'])
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->orderBy('start_date')
            ->get();
        
        // Get office closures and holidays
        $closures = OfficeClosure::query()
            ->with(['creator'])
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->orderBy('start_date')
            ->get();
        
        // Merge both collections
        return $leaves->merge($closures);
    }

    public function getResources(array $fetchInfo = []): Collection|array
    {
        // We can optionally add resources (like departments or teams) here
        // For now, we'll just return an empty collection
        return collect();
    }

    public function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action('refreshEvents'),
        ];
    }

    public function refreshEvents(): void
    {
        // Livewire will automatically re-render the widget when this action completes,
        // which will call getEvents() again to fetch fresh data from the database
        
        \Filament\Notifications\Notification::make()
            ->title('Calendar Refreshed')
            ->body('Leave and office closures calendar has been updated.')
            ->success()
            ->send();
    }

    public function getEventContent(): null|string|array
    {
        return view('filament.widgets.leave-calendar-event');
    }
}
