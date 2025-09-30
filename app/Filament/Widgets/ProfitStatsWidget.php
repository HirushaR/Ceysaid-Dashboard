<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\VendorBill;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProfitStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    
    protected static ?int $sort = 5;

    protected function getStats(): array
    {
        // Only show this widget to admins
        if (!Auth::user()?->isAdmin()) {
            return [];
        }

        $now = now();
        
        // Calculate total profit (total revenue - total vendor costs)
        $totalRevenue = Invoice::sum('total_amount');
        $totalVendorCosts = VendorBill::sum('bill_amount');
        $totalProfit = $totalRevenue - $totalVendorCosts;

        // Calculate this month profit
        $thisMonthRevenue = Invoice::whereYear('created_at', $now->year)
            ->whereMonth('created_at', $now->month)
            ->sum('total_amount');
        $thisMonthVendorCosts = VendorBill::whereYear('created_at', $now->year)
            ->whereMonth('created_at', $now->month)
            ->sum('bill_amount');
        $thisMonthProfit = $thisMonthRevenue - $thisMonthVendorCosts;

        // Calculate this week profit
        $thisWeekRevenue = Invoice::whereBetween('created_at', [
            $now->startOfWeek(),
            $now->endOfWeek()
        ])->sum('total_amount');
        $thisWeekVendorCosts = VendorBill::whereBetween('created_at', [
            $now->startOfWeek(),
            $now->endOfWeek()
        ])->sum('bill_amount');
        $thisWeekProfit = $thisWeekRevenue - $thisWeekVendorCosts;

        // Calculate today profit
        $todayRevenue = Invoice::whereDate('created_at', $now->toDateString())
            ->sum('total_amount');
        $todayVendorCosts = VendorBill::whereDate('created_at', $now->toDateString())
            ->sum('bill_amount');
        $todayProfit = $todayRevenue - $todayVendorCosts;

        return [
            Stat::make('Total Profit', 'LKR ' . number_format($totalProfit, 2))
                ->description('All time')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($totalProfit >= 0 ? 'success' : 'danger'),
                
            Stat::make('This Month', 'LKR ' . number_format($thisMonthProfit, 2))
                ->description($now->format('F Y'))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($thisMonthProfit >= 0 ? 'success' : 'danger'),
                
            Stat::make('This Week', 'LKR ' . number_format($thisWeekProfit, 2))
                ->description($now->startOfWeek()->format('M j') . ' - ' . $now->endOfWeek()->format('M j'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color($thisWeekProfit >= 0 ? 'success' : 'danger'),
                
            Stat::make('Today', 'LKR ' . number_format($todayProfit, 2))
                ->description($now->format('l, M j'))
                ->descriptionIcon('heroicon-m-clock')
                ->color($todayProfit >= 0 ? 'success' : 'danger'),
        ];
    }

    public static function canView(): bool
    {
        return Auth::user()?->isAdmin() ?? false;
    }
}
