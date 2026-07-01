<?php

namespace App\Filament\Widgets\TourFinance;

use App\Services\TourFinanceFilterStore;
use App\Services\TourFinanceReportService;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;

class TourProfitTableWidget extends Widget
{
    protected static string $view = 'filament.widgets.tour-finance.data-table';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 4;

    public function getHeading(): string
    {
        return 'Tour-wise expected profit';
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return ['Tour code', 'Tour name', 'Departure', 'Sales', 'Vendor cost', 'Gross profit', 'GP %'];
    }

    public function getRows(): Collection
    {
        $filters = TourFinanceFilterStore::get();

        return app(TourFinanceReportService::class)
            ->tourWiseProfit($filters)
            ->map(fn (array $row) => [
                $row['tour_code'],
                $row['tour_name'],
                $row['departure_date'] ?? '—',
                'LKR '.number_format($row['sales_value'], 2),
                'LKR '.number_format($row['vendor_cost'], 2),
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
