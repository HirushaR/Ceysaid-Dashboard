<?php

namespace App\Filament\Pages;

use App\Services\AnalyticsFilterStore;
use App\Services\DateRangeService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;

class AnalyticsDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Analytics';

    protected static ?string $title = 'Analytics Dashboard';

    protected static ?string $navigationGroup = 'Analytics';

    protected static string $view = 'filament.pages.analytics-dashboard';

    public ?string $datePreset = DateRangeService::PRESET_LAST_30_DAYS;

    public ?string $startDate = null;

    public ?string $endDate = null;

    public ?string $salesUser = null;

    public ?string $operationUser = null;

    public ?string $leadSource = null;

    public ?string $pipelineStage = null;

    public function mount(): void
    {
        $stored = AnalyticsFilterStore::getState();

        $this->form->fill($stored);
        $this->syncFilterPropertiesFromForm();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('datePreset')
                    ->label('Date Range')
                    ->options((new DateRangeService())->getPresetOptions())
                    ->live(),
                DatePicker::make('startDate')
                    ->label('Start Date')
                    ->visible(fn () => $this->datePreset === DateRangeService::PRESET_CUSTOM),
                DatePicker::make('endDate')
                    ->label('End Date')
                    ->visible(fn () => $this->datePreset === DateRangeService::PRESET_CUSTOM),
                Select::make('salesUser')
                    ->label('Sales User')
                    ->options(\App\Models\User::query()->where('role', 'sales')->pluck('name', 'id'))
                    ->searchable()
                    ->placeholder('All Sales Users'),
                Select::make('operationUser')
                    ->label('Operation User')
                    ->options(\App\Models\User::query()->where('role', 'operation')->pluck('name', 'id'))
                    ->searchable()
                    ->placeholder('All Operation Users'),
                Select::make('leadSource')
                    ->label('Lead Source')
                    ->options([
                        'facebook' => 'Facebook',
                        'whatsapp' => 'WhatsApp',
                        'email' => 'Email',
                    ])
                    ->placeholder('All Sources'),
                Select::make('pipelineStage')
                    ->label('Pipeline Stage')
                    ->options(\App\Enums\LeadStatus::options())
                    ->placeholder('All Stages'),
            ])
            ->columns(4);
    }

    public function applyFilters(): void
    {
        $this->syncFilterPropertiesFromForm();
        AnalyticsFilterStore::set($this->form->getState());
        $this->dispatch('analytics-filters-applied');

        \Filament\Notifications\Notification::make()
            ->title('Filters applied')
            ->body('Analytics filters have been successfully applied.')
            ->success()
            ->send();
    }

    public function resetFilters(): void
    {
        $this->datePreset = DateRangeService::PRESET_LAST_30_DAYS;
        $this->startDate = null;
        $this->endDate = null;
        $this->salesUser = null;
        $this->operationUser = null;
        $this->leadSource = null;
        $this->pipelineStage = null;

        $this->form->fill([
            'datePreset' => $this->datePreset,
            'startDate' => null,
            'endDate' => null,
            'salesUser' => null,
            'operationUser' => null,
            'leadSource' => null,
            'pipelineStage' => null,
        ]);

        AnalyticsFilterStore::clear();
        $this->dispatch('analytics-filters-applied');

        \Filament\Notifications\Notification::make()
            ->title('Filters reset')
            ->body('All filters have been reset to default values.')
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
        return auth()->user()?->isAdmin() ?? false;
    }

    private function syncFilterPropertiesFromForm(): void
    {
        $state = $this->form->getState();

        $this->datePreset = $state['datePreset'] ?? DateRangeService::PRESET_LAST_30_DAYS;
        $this->startDate = $state['startDate'] ?? null;
        $this->endDate = $state['endDate'] ?? null;
        $this->salesUser = $state['salesUser'] ?? null;
        $this->operationUser = $state['operationUser'] ?? null;
        $this->leadSource = $state['leadSource'] ?? null;
        $this->pipelineStage = $state['pipelineStage'] ?? null;
    }
}
