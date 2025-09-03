<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\CustomerPayment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class RevenueStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    
    protected static ?int $sort = 4;

    protected function getStats(): array
    {
        // Only show this widget to admins
        if (!Auth::user()?->isAdmin()) {
            return [];
        }

        $now = now();
        
        // Calculate different time periods for revenue
        $totalRevenue = Invoice::sum('total_amount');
        $thisMonthRevenue = Invoice::whereYear('created_at', $now->year)
            ->whereMonth('created_at', $now->month)
            ->sum('total_amount');
        $thisWeekRevenue = Invoice::whereBetween('created_at', [
            $now->startOfWeek(),
            $now->endOfWeek()
        ])->sum('total_amount');
        $todayRevenue = Invoice::whereDate('created_at', $now->toDateString())
            ->sum('total_amount');

        // Calculate received payments for each period
        $totalReceived = CustomerPayment::sum('amount');
        $thisMonthReceived = CustomerPayment::whereYear('payment_date', $now->year)
            ->whereMonth('payment_date', $now->month)
            ->sum('amount');
        $thisWeekReceived = CustomerPayment::whereBetween('payment_date', [
            $now->startOfWeek(),
            $now->endOfWeek()
        ])->sum('amount');
        $todayReceived = CustomerPayment::whereDate('payment_date', $now->toDateString())
            ->sum('amount');

        return [
            Stat::make('Total Revenue', '$' . number_format($totalRevenue, 2))
                ->description('All time')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
                
            Stat::make('This Month', '$' . number_format($thisMonthRevenue, 2))
                ->description($now->format('F Y'))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),
                
            Stat::make('This Week', '$' . number_format($thisWeekRevenue, 2))
                ->description($now->startOfWeek()->format('M j') . ' - ' . $now->endOfWeek()->format('M j'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),
                
            Stat::make('Today', '$' . number_format($todayRevenue, 2))
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
