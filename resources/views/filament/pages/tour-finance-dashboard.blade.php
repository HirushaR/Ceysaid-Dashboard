<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
            <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Filters</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        All amounts in LKR. Advance payments are receivables until tour departure; profit is recognised by departure month for departed tours.
                    </p>
                </div>
                <div class="flex shrink-0 gap-2">
                    <x-filament::button wire:click="resetFilters" color="gray" icon="heroicon-o-arrow-path">
                        Reset
                    </x-filament::button>
                    <x-filament::button wire:click="applyFilters" icon="heroicon-o-funnel">
                        Apply filters
                    </x-filament::button>
                </div>
            </div>
            {{ $this->form }}
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
            @livewire(\App\Filament\Widgets\TourFinance\TourFinanceKpiWidget::class, key('tour-finance-kpi'))
            @livewire(\App\Filament\Widgets\ProfitStatsWidget::class, key('tour-finance-profit-stats'))
        </div>

        <div class="space-y-6">
            @livewire(\App\Filament\Widgets\TourFinance\MonthlyReceivablesWidget::class, key('tour-finance-receivables'))
            @livewire(\App\Filament\Widgets\TourFinance\MonthlyPayablesWidget::class, key('tour-finance-payables'))
            @livewire(\App\Filament\Widgets\TourFinance\TourProfitTableWidget::class, key('tour-finance-profit-table'))
            @livewire(\App\Filament\Widgets\TourFinance\TourCashGapWidget::class, key('tour-finance-cash-gap'))
            @livewire(\App\Filament\Widgets\TourFinance\DepartureMonthProfitWidget::class, key('tour-finance-departure-profit'))
        </div>
    </div>
</x-filament-panels::page>
