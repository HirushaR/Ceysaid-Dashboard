<?php

namespace App\Filament\Resources\AllLeadDashboardResource\Pages;

use App\Filament\Resources\AllLeadDashboardResource;
use App\Filament\Resources\LeadResource;
use App\Filament\Widgets\AllLeadMetricsWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use App\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Builder;

class ListAllLeadDashboards extends ListRecords
{
    protected static string $resource = AllLeadDashboardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('new_lead')
                ->label('New Lead')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->url(LeadResource::getUrl('create'))
                ->button(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AllLeadMetricsWidget::class,
        ];
    }

    public function getTabs(): array
    {
        $baseQuery = function() {
            return AllLeadDashboardResource::getEloquentQuery();
        };

        return [
            'all' => Tab::make('All')
                ->badge($baseQuery()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query),

            'new' => Tab::make('New')
                ->badge($baseQuery()->where('status', LeadStatus::NEW->value)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', LeadStatus::NEW->value)),

            'sales' => Tab::make('In Sales')
                ->badge($baseQuery()->where('status', LeadStatus::ASSIGNED_TO_SALES->value)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', LeadStatus::ASSIGNED_TO_SALES->value)),

            'operations' => Tab::make('In Operations')
                ->badge($baseQuery()->where('status', LeadStatus::ASSIGNED_TO_OPERATIONS->value)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', LeadStatus::ASSIGNED_TO_OPERATIONS->value)),

            'pricing' => Tab::make('Pricing')
                ->badge($baseQuery()->where('status', LeadStatus::PRICING_IN_PROGRESS->value)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', LeadStatus::PRICING_IN_PROGRESS->value)),

            'confirmed' => Tab::make('Confirmed')
                ->badge($baseQuery()->where('status', LeadStatus::CONFIRMED->value)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', LeadStatus::CONFIRMED->value)),

            'closed' => Tab::make('Closed')
                ->badge($baseQuery()->where('status', LeadStatus::MARK_CLOSED->value)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', LeadStatus::MARK_CLOSED->value)),
        ];
    }
} 