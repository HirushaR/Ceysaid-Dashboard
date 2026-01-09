<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class RemainingLeavesWidget extends BaseWidget
{
    protected static bool $isDiscovered = false;
    
    protected static ?string $pollingInterval = '30s';
    
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $user = Auth::user();
        
        if (!$user) {
            return [];
        }

        // Get remaining leaves for current year
        $remaining = $user->getRemainingLeaves();
        $allocations = $user->getLeaveAllocations();
        $used = $user->getUsedLeaves();
        $currentYear = now()->year;

        return [
            Stat::make('Casual Leave', number_format($remaining['casual']) . ' days')
                ->description("Used: {$used['casual']} / {$allocations['casual']} days")
                ->descriptionIcon('heroicon-m-calendar')
                ->color($remaining['casual'] > 3 ? 'success' : ($remaining['casual'] > 0 ? 'warning' : 'danger')),
                
            Stat::make('Sick Leave', number_format($remaining['sick']) . ' days')
                ->description("Used: {$used['sick']} / {$allocations['sick']} days")
                ->descriptionIcon('heroicon-m-heart')
                ->color($remaining['sick'] > 3 ? 'success' : ($remaining['sick'] > 0 ? 'warning' : 'danger')),
                
            Stat::make('Annual Leave', number_format($remaining['annual']) . ' days')
                ->description("Used: {$used['annual']} / {$allocations['annual']} days")
                ->descriptionIcon('heroicon-m-sun')
                ->color($remaining['annual'] > 7 ? 'success' : ($remaining['annual'] > 0 ? 'warning' : 'danger')),
                
            Stat::make('Total Remaining', number_format($remaining['total']) . ' days')
                ->description("Used: {$used['total']} / {$allocations['total']} days • {$currentYear}")
                ->descriptionIcon('heroicon-m-clock')
                ->color($remaining['total'] > 14 ? 'success' : ($remaining['total'] > 7 ? 'info' : ($remaining['total'] > 0 ? 'warning' : 'danger'))),
        ];
    }

    public static function canView(): bool
    {
        return Auth::check();
    }
}

