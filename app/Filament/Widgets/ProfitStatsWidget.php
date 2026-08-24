<?php

namespace App\Filament\Widgets;

use App\Enums\TourStatus;
use App\Models\Tour;
use App\Services\TourFinanceFilterStore;
use App\Services\TourFinanceReportService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class ProfitStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected static ?int $sort = 5;

    #[On('tour-finance-filters-applied')]
    public function refreshStats(): void
    {
        //
    }

    protected function getStats(): array
    {
        $user = Auth::user();
        if (! $user?->isAdmin() && ! $user?->isAccount()) {
            return [];
        }

        $service = app(TourFinanceReportService::class);
        $filters = TourFinanceFilterStore::get();
        $now = now();

        $allToursProfit = $service->tourWiseProfit($filters)->sum('gross_profit');

        $thisMonthKey = $now->format('Y-m');
        $monthFilters = array_merge($filters, [
            'departure_from' => $now->copy()->startOfMonth()->toDateString(),
            'departure_to' => $now->copy()->endOfMonth()->toDateString(),
        ]);
        $thisMonthRow = $service->departureMonthProfit($monthFilters)->firstWhere('month_key', $thisMonthKey);
        $thisMonthProfit = (float) ($thisMonthRow['gross_profit'] ?? 0);

        $openToursQuery = Tour::query()->where('status', TourStatus::Open);
        if (! empty($filters['tour_id'])) {
            $openToursQuery->where('id', $filters['tour_id']);
        }
        $openToursCount = $openToursQuery->count();
        $urgentGaps = $service->tourCashGap($filters)->where('is_urgent', true)->count();

        return [
            Stat::make('Expected tour profit', 'LKR '.number_format($allToursProfit, 2))
                ->description('All tours (sales − vendor cost)')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($allToursProfit >= 0 ? 'success' : 'danger'),

            Stat::make('Departure-month profit', 'LKR '.number_format($thisMonthProfit, 2))
                ->description($now->format('F Y').' (departed tours)')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($thisMonthProfit >= 0 ? 'success' : 'danger'),

            Stat::make('Open tours', (string) $openToursCount)
                ->description('Active departures')
                ->descriptionIcon('heroicon-m-map')
                ->color('info'),

            Stat::make('Urgent cash gaps', (string) $urgentGaps)
                ->description('Negative gap within 30 days')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($urgentGaps > 0 ? 'danger' : 'success'),
        ];
    }

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user && ($user->isAdmin() || $user->isAccount());
    }
}
