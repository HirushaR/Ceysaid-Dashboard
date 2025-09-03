<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use App\Models\Invoice;
use App\Enums\LeadStatus;
use App\Enums\ServiceStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class OperationKPIWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    
    protected static ?int $sort = 1;


    protected function getStats(): array
    {
        $user = Auth::user();
        
        // Only show this widget to operation users and admins
        if (!$user || (!$user->isOperation())) {
            return [];
        }

        $now = now();
        $userId = $user->id;
        
        // Get leads assigned to this operation user
        $assignedLeadsQuery = Lead::where('assigned_operator', $userId);
        
        // Calculate different time periods for assigned leads
        $totalAssignedLeads = $assignedLeadsQuery->count();
        $thisMonthAssignedLeads = (clone $assignedLeadsQuery)->whereYear('created_at', $now->year)
            ->whereMonth('created_at', $now->month)
            ->count();
        $thisWeekAssignedLeads = (clone $assignedLeadsQuery)->whereBetween('created_at', [
            $now->startOfWeek(),
            $now->endOfWeek()
        ])->count();
        
        // Get completion metrics
        $completedLeads = (clone $assignedLeadsQuery)->where('status', LeadStatus::OPERATION_COMPLETE->value)->count();
        $completionRate = $totalAssignedLeads > 0 ? round(($completedLeads / $totalAssignedLeads) * 100, 1) : 0;
        
        // Get service status metrics
        $pendingServices = (clone $assignedLeadsQuery)->where(function($query) {
            $query->where('air_ticket_status', ServiceStatus::PENDING->value)
                  ->orWhere('hotel_status', ServiceStatus::PENDING->value)
                  ->orWhere('visa_status', ServiceStatus::PENDING->value)
                  ->orWhere('land_package_status', ServiceStatus::PENDING->value);
        })->count();
        
        $completedServices = (clone $assignedLeadsQuery)->where(function($query) {
            $query->where('air_ticket_status', ServiceStatus::DONE->value)
                  ->orWhere('hotel_status', ServiceStatus::DONE->value)
                  ->orWhere('visa_status', ServiceStatus::DONE->value)
                  ->orWhere('land_package_status', ServiceStatus::DONE->value);
        })->count();
        
        // Get active leads (assigned to operations but not completed)
        $activeLeads = (clone $assignedLeadsQuery)->whereIn('status', [
            LeadStatus::ASSIGNED_TO_OPERATIONS->value,
            LeadStatus::INFO_GATHER_COMPLETE->value
        ])->count();
        
        // Get leads requiring immediate attention (newly assigned or overdue)
        $urgentLeads = (clone $assignedLeadsQuery)->where('status', LeadStatus::ASSIGNED_TO_OPERATIONS->value)
            ->where('created_at', '<=', $now->subDays(3)) // Leads older than 3 days
            ->count();

        return [
            Stat::make('Total Assigned Leads', number_format($totalAssignedLeads))
                ->description('All time')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('primary'),
                
            Stat::make('Completion Rate', $completionRate . '%')
                ->description($completedLeads . ' completed out of ' . $totalAssignedLeads)
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($completionRate >= 80 ? 'success' : ($completionRate >= 60 ? 'warning' : 'danger')),
                
            Stat::make('Active Operations', number_format($activeLeads))
                ->description('Currently in progress')
                ->descriptionIcon('heroicon-m-cog-6-tooth')
                ->color('info'),
                
            Stat::make('This Month', number_format($thisMonthAssignedLeads))
                ->description($now->format('F Y'))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('success'),
                
            Stat::make('Pending Services', number_format($pendingServices))
                ->description('Services awaiting completion')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
                
            Stat::make('Urgent Attention', number_format($urgentLeads))
                ->description('Leads older than 3 days')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($urgentLeads > 0 ? 'danger' : 'success'),
        ];
    }

    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && ($user->isOperation() || $user->isAdmin());
    }
}
