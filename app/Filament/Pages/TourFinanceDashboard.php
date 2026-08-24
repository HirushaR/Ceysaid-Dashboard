<?php

namespace App\Filament\Pages;

use App\Enums\TourStatus;
use App\Models\Tour;
use App\Models\User;
use App\Services\TourFinanceFilterStore;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;

class TourFinanceDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Tour Finance Control';

    protected static ?string $title = 'Tour Finance Control';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.tour-finance-dashboard';

    public ?string $tourId = null;

    public ?string $salesUserId = null;

    public ?string $tourStatus = null;

    public ?string $departureFrom = null;

    public ?string $departureTo = null;

    public function mount(): void
    {
        $stored = TourFinanceFilterStore::get();

        $this->form->fill([
            'tourId' => isset($stored['tour_id']) ? (string) $stored['tour_id'] : null,
            'salesUserId' => isset($stored['sales_user_id']) ? (string) $stored['sales_user_id'] : null,
            'tourStatus' => $stored['tour_status'] ?? null,
            'departureFrom' => $stored['departure_from'] ?? null,
            'departureTo' => $stored['departure_to'] ?? null,
        ]);

        $this->syncFilterPropertiesFromForm();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('tourId')
                    ->label('Tour')
                    ->options(fn () => Tour::query()
                        ->orderByDesc('departure_date')
                        ->get()
                        ->mapWithKeys(fn (Tour $tour) => [
                            $tour->id => "{$tour->tour_code} — {$tour->name}",
                        ])
                        ->all())
                    ->searchable()
                    ->placeholder('All tours'),
                Select::make('salesUserId')
                    ->label('Salesperson')
                    ->options(fn () => User::query()->where('role', 'sales')->pluck('name', 'id'))
                    ->searchable()
                    ->placeholder('All sales'),
                Select::make('tourStatus')
                    ->label('Tour status')
                    ->options(TourStatus::options())
                    ->placeholder('All statuses'),
                DatePicker::make('departureFrom')
                    ->label('Departure from'),
                DatePicker::make('departureTo')
                    ->label('Departure to'),
            ])
            ->columns(3);
    }

    /**
     * @return array<string, mixed>
     */
    public function getFilters(): array
    {
        return array_filter([
            'tour_id' => $this->tourId ? (int) $this->tourId : null,
            'sales_user_id' => $this->salesUserId ? (int) $this->salesUserId : null,
            'tour_status' => $this->tourStatus,
            'departure_from' => $this->departureFrom,
            'departure_to' => $this->departureTo,
        ], fn ($value) => $value !== null && $value !== '');
    }

    public function applyFilters(): void
    {
        $this->syncFilterPropertiesFromForm();
        TourFinanceFilterStore::set($this->getFilters());
        $this->dispatch('tour-finance-filters-applied');

        \Filament\Notifications\Notification::make()
            ->title('Filters applied')
            ->success()
            ->send();
    }

    public function resetFilters(): void
    {
        $this->tourId = null;
        $this->salesUserId = null;
        $this->tourStatus = null;
        $this->departureFrom = null;
        $this->departureTo = null;

        $this->form->fill([
            'tourId' => null,
            'salesUserId' => null,
            'tourStatus' => null,
            'departureFrom' => null,
            'departureTo' => null,
        ]);

        TourFinanceFilterStore::clear();
        $this->dispatch('tour-finance-filters-applied');

        \Filament\Notifications\Notification::make()
            ->title('Filters reset')
            ->info()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->isAdmin() || $user->isAccount());
    }

    private function syncFilterPropertiesFromForm(): void
    {
        $state = $this->form->getState();

        $this->tourId = $state['tourId'] ?? null;
        $this->salesUserId = $state['salesUserId'] ?? null;
        $this->tourStatus = $state['tourStatus'] ?? null;
        $this->departureFrom = $state['departureFrom'] ?? null;
        $this->departureTo = $state['departureTo'] ?? null;
    }
}
