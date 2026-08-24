<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
            <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Filters</h3>
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

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
            @livewire(\App\Filament\Widgets\Analytics\KPICardsWidget::class, key('analytics-kpi'))
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            @livewire(\App\Filament\Widgets\Analytics\SalesStaffPerformanceWidget::class, key('analytics-sales-staff'))
            @livewire(\App\Filament\Widgets\Analytics\LeadsTrendWidget::class, key('analytics-leads-trend'))
            @livewire(\App\Filament\Widgets\Analytics\RevenueTrendWidget::class, key('analytics-revenue-trend'))
            @livewire(\App\Filament\Widgets\Analytics\SalesPerformanceWidget::class, key('analytics-sales-performance'))
            @livewire(\App\Filament\Widgets\Analytics\PipelineBreakdownWidget::class, key('analytics-pipeline'))
            @livewire(\App\Filament\Widgets\Analytics\OperationsWorkloadWidget::class, key('analytics-operations'))
        </div>

        @auth
            @if(auth()->user()->isAdmin())
                <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                    @livewire(\App\Filament\Widgets\LeaveCalendarWidget::class, key('analytics-leave-calendar'))
                </div>
            @endif
        @endauth
    </div>
</x-filament-panels::page>
