<?php

namespace App\Filament\Widgets\TourFinance;

use App\Services\TourFinanceFilterStore;
use App\Services\TourFinanceReportService;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;

class TourCashGapWidget extends Widget
{
    protected static string $view = 'filament.widgets.tour-finance.cash-gap-table';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 5;

    public function getHeading(): string
    {
        return 'Cash gap before departure';
    }

    public function getCashGapRows(): Collection
    {
        $filters = TourFinanceFilterStore::get();

        return app(TourFinanceReportService::class)->tourCashGap($filters);
    }

    #[On('tour-finance-filters-applied')]
    public function refreshTable(): void
    {
        //
    }
}
