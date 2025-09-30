<?php

namespace App\Filament\Pages;

use App\Services\DateRangeService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Cache;

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

    protected ?DateRangeService $dateRange = null;

    protected static ?AnalyticsDashboard $currentInstance = null;

    public function mount(): void
    {
        self::$currentInstance = $this;
        $this->initializeDateRange();
    }

    public static function getCurrentInstance(): ?self
    {
        return self::$currentInstance;
    }

    protected function initializeDateRange(): void
    {
        $this->dateRange = new DateRangeService();
        $this->dateRange->setPreset($this->datePreset);
        
        if ($this->datePreset === DateRangeService::PRESET_CUSTOM) {
            $this->dateRange->setPreset(DateRangeService::PRESET_CUSTOM, [
                'start' => $this->startDate,
                'end' => $this->endDate,
            ]);
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('datePreset')
                    ->label('Date Range')
                    ->options((new DateRangeService())->getPresetOptions()),
                
                DatePicker::make('startDate')
                    ->label('Start Date')
                    ->visible(fn () => $this->datePreset === DateRangeService::PRESET_CUSTOM),
                
                DatePicker::make('endDate')
                    ->label('End Date')
                    ->visible(fn () => $this->datePreset === DateRangeService::PRESET_CUSTOM),

                Select::make('salesUser')
                    ->label('Sales User')
                    ->options(\App\Models\User::where('role', 'sales')->pluck('name', 'id'))
                    ->searchable()
                    ->placeholder('All Sales Users'),

                Select::make('operationUser')
                    ->label('Operation User')
                    ->options(\App\Models\User::where('role', 'operation')->pluck('name', 'id'))
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
        // The form state is already bound to the class properties
        // No need to manually update them since they're already updated by the form
        
        // Reinitialize date range with current settings
        $this->initializeDateRange();
        
        // Clear cache to force widget refresh
        $this->clearCache();
        
        // Show success notification
        \Filament\Notifications\Notification::make()
            ->title('Filters Applied')
            ->body('Analytics filters have been successfully applied.')
            ->success()
            ->send();
    }

    public function resetFilters(): void
    {
        // Reset to default values
        $this->datePreset = DateRangeService::PRESET_LAST_30_DAYS;
        $this->startDate = null;
        $this->endDate = null;
        $this->salesUser = null;
        $this->operationUser = null;
        $this->leadSource = null;
        $this->pipelineStage = null;
        
        // Reinitialize date range
        $this->initializeDateRange();
        
        // Clear cache
        $this->clearCache();
        
        // Reset form state
        $this->form->fill([
            'datePreset' => $this->datePreset,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'salesUser' => $this->salesUser,
            'operationUser' => $this->operationUser,
            'leadSource' => $this->leadSource,
            'pipelineStage' => $this->pipelineStage,
        ]);
        
        // Show success notification
        \Filament\Notifications\Notification::make()
            ->title('Filters Reset')
            ->body('All filters have been reset to default values.')
            ->info()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resetFilters')
                ->label('Reset')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action('resetFilters'),
            Action::make('applyFilters')
                ->label('Apply Filters')
                ->icon('heroicon-o-funnel')
                ->color('primary')
                ->action('applyFilters'),
        ];
    }

    protected function clearCache(): void
    {
        $cacheKey = $this->getCacheKey();
        Cache::forget($cacheKey);
    }

    public function getCacheKey(): string
    {
        if (!$this->dateRange) {
            $this->initializeDateRange();
        }
        
        return 'analytics_' . auth()->id() . '_' . md5(serialize([
            'date_range' => $this->dateRange->toArray(),
            'filters' => [
                'sales_user' => $this->salesUser,
                'operation_user' => $this->operationUser,
                'lead_source' => $this->leadSource,
                'pipeline_stage' => $this->pipelineStage,
            ],
        ]));
    }

    public function getDateRange(): DateRangeService
    {
        if (!$this->dateRange) {
            $this->initializeDateRange();
        }
        
        return $this->dateRange;
    }

    public function getFilters(): array
    {
        return [
            'sales_user' => $this->salesUser,
            'operation_user' => $this->operationUser,
            'lead_source' => $this->leadSource,
            'pipeline_stage' => $this->pipelineStage,
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\Analytics\KPICardsWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
           // \App\Filament\Widgets\LeaveCalendarWidget::class,
            // \App\Filament\Widgets\Analytics\LeadsTrendWidget::class,
            // \App\Filament\Widgets\Analytics\RevenueTrendWidget::class,
            // \App\Filament\Widgets\Analytics\SalesPerformanceWidget::class,
            // \App\Filament\Widgets\Analytics\PipelineBreakdownWidget::class,
            // \App\Filament\Widgets\Analytics\OperationsWorkloadWidget::class,
            
        ];
    }
}
