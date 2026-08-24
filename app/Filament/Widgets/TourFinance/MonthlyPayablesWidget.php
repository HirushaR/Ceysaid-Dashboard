<?php

namespace App\Filament\Widgets\TourFinance;

use App\Services\TourFinanceFilterStore;
use App\Services\TourFinanceReportService;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;

class MonthlyPayablesWidget extends Widget
{
    protected static string $view = 'filament.widgets.tour-finance.data-table';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public function getHeading(): string
    {
        return 'Monthly vendor payables due';
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return ['Month', 'Vendor payable'];
    }

    public function getRows(): Collection
    {
        $filters = TourFinanceFilterStore::get();

        return app(TourFinanceReportService::class)
            ->monthlyPayables($filters)
            ->map(fn (array $row) => [
                $row['month'],
                'LKR '.number_format($row['amount'], 2),
            ]);
    }

    #[On('tour-finance-filters-applied')]
    public function refreshTable(): void
    {
        //
    }
}
