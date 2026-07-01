<?php

namespace App\Filament\Widgets\TourFinance;

use App\Services\TourFinanceFilterStore;
use App\Services\TourFinanceReportService;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;

class DepartureMonthProfitWidget extends Widget
{
    protected static string $view = 'filament.widgets.tour-finance.data-table';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 6;

    public function getHeading(): string
    {
        return 'Actual profit by departure month';
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return ['Month', 'Revenue recognised', 'Cost recognised', 'Gross profit', 'GP %'];
    }

    public function getRows(): Collection
    {
        $filters = TourFinanceFilterStore::get();

        return app(TourFinanceReportService::class)
            ->departureMonthProfit($filters)
            ->map(fn (array $row) => [
                $row['month'],
                'LKR '.number_format($row['revenue'], 2),
                'LKR '.number_format($row['cost'], 2),
                'LKR '.number_format($row['gross_profit'], 2),
                $row['gp_percent'].'%',
            ]);
    }

    #[On('tour-finance-filters-applied')]
    public function refreshTable(): void
    {
        //
    }
}
