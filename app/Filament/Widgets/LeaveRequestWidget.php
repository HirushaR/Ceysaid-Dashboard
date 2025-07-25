<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Leave;
use App\Enums\LeaveStatus;
use Filament\Support\Colors\Color;

class LeaveRequestWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        if (!$user) {
            return [];
        }

        $pendingLeaves = Leave::where('user_id', $user->id)
            ->where('status', LeaveStatus::PENDING)
            ->count();

        $approvedLeaves = Leave::where('user_id', $user->id)
            ->where('status', LeaveStatus::APPROVED)
            ->count();

        $totalLeaves = Leave::where('user_id', $user->id)->count();

        return [
            Stat::make('📋 My Leave Requests', $totalLeaves)
                ->description('Total requests submitted')
                ->color('primary')
                ->url(route('filament.admin.resources.leave-requests.index'))
                ->descriptionIcon('heroicon-m-eye'),

            Stat::make('⏳ Pending Approval', $pendingLeaves)
                ->description('Awaiting HR review')
                ->color($pendingLeaves > 0 ? 'warning' : 'gray')
                ->descriptionIcon('heroicon-m-clock'),

            Stat::make('✅ Approved Leaves', $approvedLeaves)
                ->description('Successfully approved')
                ->color('success')
                ->descriptionIcon('heroicon-m-check-circle'),
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }

    public function getDisplayName(): string
    {
        return 'Leave Request Overview';
    }
}
