<?php

namespace App\Filament\Widgets;

use App\Models\CallCenterCall;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CallCenterStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    
    protected function getStats(): array
    {
        $user = auth()->user();
        
        // Only show for call center users and admins
        if (!$user || (!$user->isCallCenter())) {
            return [];
        }
        
        // Base query for call center calls
        $baseQuery = CallCenterCall::query();
        
        // If not admin, only show user's assigned calls
        if (!$user->isAdmin()) {
            $baseQuery->where('assigned_call_center_user', $user->id);
        }
        
        // Total assigned calls
        $totalAssigned = (clone $baseQuery)->where('status', '!=', CallCenterCall::STATUS_PENDING)->count();
        
        // Pending calls (assigned but not called)
        $pendingCalls = (clone $baseQuery)
            ->where('status', CallCenterCall::STATUS_ASSIGNED)
            ->count();
        
        // Not answered calls (priority)
        $notAnswered = (clone $baseQuery)
            ->where('status', CallCenterCall::STATUS_NOT_ANSWERED)
            ->count();
        
        // Completed calls today
        $completedToday = (clone $baseQuery)
            ->where('status', CallCenterCall::STATUS_COMPLETED)
            ->whereDate('last_call_attempt', Carbon::today())
            ->count();
        
        // Total calls made today
        $callsToday = (clone $baseQuery)
            ->whereDate('last_call_attempt', Carbon::today())
            ->count();
        
        return [
            Stat::make('Total Assigned', $totalAssigned)
                ->description('Calls assigned to call center')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),
                
            Stat::make('Pending Calls', $pendingCalls)
                ->description('Calls waiting to be made')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
                
            Stat::make('Not Answered', $notAnswered)
                ->description('Calls that need follow-up')
                ->descriptionIcon('heroicon-m-phone-x-mark')
                ->color('danger'),
                
            Stat::make('Completed Today', $completedToday)
                ->description('Successfully completed calls')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
                
            Stat::make('Calls Made Today', $callsToday)
                ->description('Total call attempts today')
                ->descriptionIcon('heroicon-m-phone')
                ->color('primary'),
        ];
    }
}

