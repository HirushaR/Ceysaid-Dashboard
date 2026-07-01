<?php

namespace App\Filament\Widgets\TourFinance;

use App\Services\TourFinanceFilterStore;
use App\Services\TourFinanceReportService;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;

class MonthlyReceivablesWidget extends Widget
{
    protected static string $view = 'filament.widgets.tour-finance.data-table';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function getHeading(): string
    {
        return 'Monthly receivables due';
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return ['Month', 'Balance receivable'];
    }

    public function getRows(): Collection
    {
        $filters = TourFinanceFilterStore::get();

        return app(TourFinanceReportService::class)
            ->monthlyReceivables($filters)
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
