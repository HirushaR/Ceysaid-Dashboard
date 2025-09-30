<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use App\Models\Invoice;
use App\Enums\LeadStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesKPIWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = Auth::user();
        
        // Only show this widget to sales users and admins
        if (!$user || (!$user->isSales() )) {
            return [];
        }

        $now = now();
        $userId = $user->id;
        
        // Get leads assigned to this sales user
        $assignedLeadsQuery = Lead::where('assigned_to', $userId);
        
        // Calculate different time periods for assigned leads
        $totalAssignedLeads = $assignedLeadsQuery->count();
        $thisMonthAssignedLeads = (clone $assignedLeadsQuery)->whereYear('created_at', $now->year)
            ->whereMonth('created_at', $now->month)
            ->count();
        $thisWeekAssignedLeads = (clone $assignedLeadsQuery)->whereBetween('created_at', [
            $now->startOfWeek(),
            $now->endOfWeek()
        ])->count();
        
        // Get conversion metrics
        $convertedLeads = (clone $assignedLeadsQuery)->whereIn('status', [
            LeadStatus::CONFIRMED->value,
            LeadStatus::OPERATION_COMPLETE->value,
            LeadStatus::DOCUMENT_UPLOAD_COMPLETE->value
        ])->count();
        
        $conversionRate = $totalAssignedLeads > 0 ? round(($convertedLeads / $totalAssignedLeads) * 100, 1) : 0;
        
        // Get revenue metrics from invoices
        $revenueQuery = Invoice::whereHas('lead', function($query) use ($userId) {
            $query->where('assigned_to', $userId);
        });
        
        $totalRevenue = $revenueQuery->sum('total_amount');
        $thisMonthRevenue = (clone $revenueQuery)->whereYear('created_at', $now->year)
            ->whereMonth('created_at', $now->month)
            ->sum('total_amount');
        
        // Get active leads (not closed or completed)
        $activeLeads = (clone $assignedLeadsQuery)->whereNotIn('status', [
            LeadStatus::MARK_CLOSED->value,
            LeadStatus::OPERATION_COMPLETE->value,
            LeadStatus::DOCUMENT_UPLOAD_COMPLETE->value
        ])->count();
        
        // Get leads requiring attention (new, assigned, pricing in progress)
        $attentionLeads = (clone $assignedLeadsQuery)->whereIn('status', [
            LeadStatus::NEW->value,
            LeadStatus::ASSIGNED_TO_SALES->value,
            LeadStatus::PRICING_IN_PROGRESS->value
        ])->count();

        return [
            Stat::make('Total Assigned Leads', number_format($totalAssignedLeads))
                ->description('All time')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
                
            Stat::make('Conversion Rate', $conversionRate . '%')
                ->description($convertedLeads . ' converted out of ' . $totalAssignedLeads)
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($conversionRate >= 20 ? 'success' : ($conversionRate >= 10 ? 'warning' : 'danger')),
                
            Stat::make('Total Revenue', 'LKR ' . number_format($totalRevenue, 0, ',', '.'))
                ->description('From all converted leads')
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
                
            Stat::make('Needs Attention', number_format($attentionLeads))
                ->description('New, assigned, or pricing')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($attentionLeads > 5 ? 'danger' : 'warning'),
        ];
    }

    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && ($user->isSales() || $user->isAdmin());
    }
}
