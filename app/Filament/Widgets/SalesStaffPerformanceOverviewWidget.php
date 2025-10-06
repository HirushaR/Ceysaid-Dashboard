<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Lead;
use App\Models\Invoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesStaffPerformanceOverviewWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $user = Auth::user();
        
        // Only show this widget to admins
        if (!$user || !$user->isAdmin()) {
            return [];
        }

        $now = now();
        
        // Get all sales users
        $salesUsers = User::where('role', 'sales')->get();
        
        if ($salesUsers->isEmpty()) {
            return [
                Stat::make('No Sales Staff', 'No sales users found')
                    ->description('Add sales users to track performance')
                    ->descriptionIcon('heroicon-m-user-plus')
                    ->color('gray'),
            ];
        }

        // Calculate overall performance metrics
        $totalLeads = Lead::whereIn('assigned_to', $salesUsers->pluck('id'))->count();
        $convertedLeads = Lead::whereIn('assigned_to', $salesUsers->pluck('id'))
            ->whereIn('status', ['confirmed', 'operation_complete', 'document_upload_complete'])
            ->count();
        
        $conversionRate = $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 1) : 0;
        
        $totalRevenue = Invoice::whereHas('lead', function($query) use ($salesUsers) {
            $query->whereIn('assigned_to', $salesUsers->pluck('id'));
        })->sum('total_amount');
        
        $thisMonthRevenue = Invoice::whereHas('lead', function($query) use ($salesUsers) {
            $query->whereIn('assigned_to', $salesUsers->pluck('id'));
        })->whereYear('created_at', $now->year)
          ->whereMonth('created_at', $now->month)
          ->sum('total_amount');
        
        $activeLeads = Lead::whereIn('assigned_to', $salesUsers->pluck('id'))
            ->whereNotIn('status', ['mark_closed', 'operation_complete', 'document_upload_complete'])
            ->count();
        
        $pendingLeads = Lead::whereIn('assigned_to', $salesUsers->pluck('id'))
            ->whereIn('status', ['new', 'assigned_to_sales', 'pricing_in_progress'])
            ->count();

        // Get top performer
        $topPerformer = User::where('role', 'sales')
            ->withCount(['leads as converted_leads_count' => function($query) {
                $query->whereIn('status', ['confirmed', 'operation_complete', 'document_upload_complete']);
            }])
            ->orderByDesc('converted_leads_count')
            ->first();

        return [
            Stat::make('Total Sales Staff', $salesUsers->count())
                ->description('Active sales team members')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
                
            Stat::make('Overall Conversion Rate', $conversionRate . '%')
                ->description($convertedLeads . ' converted out of ' . $totalLeads . ' total leads' . 
                             ' (Leads that reached confirmed, operation complete, or document upload complete status)')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($conversionRate >= 20 ? 'success' : ($conversionRate >= 10 ? 'warning' : 'danger')),
                
            Stat::make('Total Revenue', 'LKR ' . number_format($totalRevenue, 0, ',', '.'))
                ->description('From all sales staff')
                ->descriptionIcon('heroicon-m-currency-rupee')
                ->color('success'),
                
            Stat::make('This Month Revenue', 'LKR ' . number_format($thisMonthRevenue, 0, ',', '.'))
                ->description($now->format('F Y'))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),
                
            Stat::make('Active Leads', number_format($activeLeads))
                ->description('Requiring follow-up')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
                
            Stat::make('Pending Leads', number_format($pendingLeads))
                ->description('New, assigned, or pricing')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($pendingLeads > 10 ? 'danger' : 'warning'),
                
            Stat::make('Top Performer', $topPerformer?->name ?? 'N/A')
                ->description($topPerformer ? $topPerformer->converted_leads_count . ' conversions' : 'No data')
                ->descriptionIcon('heroicon-m-star')
                ->color('success'),
        ];
    }

    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && $user->isAdmin();
    }
}
