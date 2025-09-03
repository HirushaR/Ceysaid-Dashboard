<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class LeadsStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Only show this widget to admins
        if (!Auth::user()?->isAdmin()) {
            return [];
        }

        $now = now();
        
        // Calculate different time periods
        $totalLeads = Lead::count();
        $thisMonthLeads = Lead::whereYear('created_at', $now->year)
            ->whereMonth('created_at', $now->month)
            ->count();
        $thisWeekLeads = Lead::whereBetween('created_at', [
            $now->startOfWeek(),
            $now->endOfWeek()
        ])->count();
        $todayLeads = Lead::whereDate('created_at', $now->toDateString())
            ->count();

        return [
            Stat::make('Total Leads', number_format($totalLeads))
                ->description('All time')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
                
            Stat::make('This Month', number_format($thisMonthLeads))
                ->description($now->format('F Y'))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('success'),
                
            Stat::make('This Week', number_format($thisWeekLeads))
                ->description($now->startOfWeek()->format('M j') . ' - ' . $now->endOfWeek()->format('M j'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),
                
            Stat::make('Today', number_format($todayLeads))
                ->description($now->format('l, M j'))
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }

    public static function canView(): bool
    {
        return Auth::user()?->isAdmin() ?? false;
    }
}
