<?php

namespace App\Filament\Widgets\TourFinance;

use App\Services\TourFinanceFilterStore;
use App\Services\TourFinanceReportService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\On;

class TourFinanceKpiWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    #[On('tour-finance-filters-applied')]
    public function refreshKpis(): void
    {
        //
    }

    protected function getStats(): array
    {
        $filters = TourFinanceFilterStore::get();
        $kpis = app(TourFinanceReportService::class)->portfolioKpis($filters);

        return [
            Stat::make('Outstanding receivable', 'LKR '.number_format($kpis['total_receivable'], 2))
                ->description('Balance to collect')
                ->color('warning'),
            Stat::make('Vendor payable', 'LKR '.number_format($kpis['total_payable'], 2))
                ->description('Supplier balance due')
                ->color('danger'),
            Stat::make('Net cash gap', 'LKR '.number_format($kpis['net_cash_gap'], 2))
                ->description('Receivable − payable')
                ->color($kpis['net_cash_gap'] >= 0 ? 'success' : 'danger'),
            Stat::make('Overdue receivables', 'LKR '.number_format($kpis['overdue_receivable'], 2))
                ->description('Past due date')
                ->color($kpis['overdue_receivable'] > 0 ? 'danger' : 'success'),
            Stat::make('Expected GP (open tours)', 'LKR '.number_format($kpis['expected_gross_profit'], 2))
                ->description('Open tour margin')
                ->color('info'),
        ];
    }
}
