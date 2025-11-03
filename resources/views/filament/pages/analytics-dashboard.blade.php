<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Filters Section -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Filters</h3>
                <div class="flex gap-2">
                    @foreach ($this->getHeaderActions() as $action)
                        {{ $action }}
                    @endforeach
                </div>
            </div>
            {{ $this->form }}
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach ($this->getHeaderWidgets() as $widget)
                @livewire($widget)
            @endforeach
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @foreach ($this->getFooterWidgets() as $widget)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                    @livewire($widget)
                </div>
            @endforeach
        </div>

        <!-- Leave Calendar (Full Width) - Admin Only -->
        @auth
            @if(auth()->user()->isAdmin())
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                    @livewire(\App\Filament\Widgets\LeaveCalendarWidget::class)
                </div>
            @endif
        @endauth
    </div>
</x-filament-panels::page>
