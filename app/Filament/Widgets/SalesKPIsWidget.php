<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Lead;
use App\Models\Invoice;
use App\Enums\LeadStatus;
use Carbon\Carbon;

class SalesKPIsWidget extends BaseWidget
{
    public function getHeading(): string
    {
        return 'Key Performance Indicators';
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $currentYear = Carbon::now()->year;
        
        // Get current year data for the specific user
        $leadsQuery = Lead::where('assigned_to', $user ? $user->id : null)
            ->whereYear('created_at', $currentYear);
        
        $invoicesQuery = Invoice::whereHas('lead', function($query) use ($user, $currentYear) {
            $query->where('assigned_to', $user ? $user->id : null)
                ->whereYear('created_at', $currentYear);
        });

        // Total leads for this user
        $totalLeads = $leadsQuery->count();
        
        // Confirmed leads for this user
        $confirmedLeads = $leadsQuery->where('status', LeadStatus::CONFIRMED->value)->count();
        
        // Conversion rate for this user
        $conversionRate = $totalLeads > 0 ? ($confirmedLeads / $totalLeads) * 100 : 0;
        
        // Total revenue for this user
        $totalRevenue = $invoicesQuery->sum('total_amount');
        
        // Total profit for this user
        $totalProfit = $invoicesQuery->get()->sum('profit');
        
        // Average deal size for this user
        $averageDealSize = $confirmedLeads > 0 ? $totalRevenue / $confirmedLeads : 0;
        
        // Profit margin for this user
        $profitMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;
        
        // Monthly average revenue for this user
        $monthlyRevenue = $totalRevenue / 12;
        
        // Get leads by status for this month for this user
        $thisMonthLeads = Lead::where('assigned_to', $user ? $user->id : null)
            ->whereYear('created_at', Carbon::now()->year)
            ->whereMonth('created_at', Carbon::now()->month)
            ->count();

        return [
            Stat::make('Your Conversion Rate', number_format($conversionRate, 1) . '%')
                ->description($confirmedLeads . ' confirmed out of ' . $totalLeads . ' total leads')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($conversionRate >= 20 ? 'success' : ($conversionRate >= 10 ? 'warning' : 'danger')),
                
            Stat::make('Your Average Deal Size', '$' . number_format($averageDealSize, 2))
                ->description('Per confirmed lead')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color($averageDealSize >= 1000 ? 'success' : ($averageDealSize >= 500 ? 'warning' : 'danger')),
                
            Stat::make('Your Profit Margin', number_format($profitMargin, 1) . '%')
                ->description('$' . number_format($totalProfit, 2) . ' profit on $' . number_format($totalRevenue, 2) . ' revenue')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color($profitMargin >= 20 ? 'success' : ($profitMargin >= 10 ? 'warning' : 'danger')),
                
            Stat::make('Your Monthly Average', '$' . number_format($monthlyRevenue, 2))
                ->description($thisMonthLeads . ' leads this month')
                ->descriptionIcon('heroicon-m-calendar')
                ->color($monthlyRevenue >= 5000 ? 'success' : ($monthlyRevenue >= 2000 ? 'warning' : 'danger')),
        ];
    }
} 