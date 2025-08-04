<?php

namespace App\Filament\Resources\MySalesDashboardResource\Pages;

use App\Filament\Resources\MySalesDashboardResource;
use App\Filament\Resources\LeadResource;
use App\Filament\Widgets\LeadsByStatusWidget;
use App\Filament\Widgets\RevenueProfitTrendWidget;
use App\Filament\Widgets\SalesKPIsWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use App\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Builder;

class ListMySalesDashboards extends ListRecords
{
    protected static string $resource = MySalesDashboardResource::class;

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
        return [];
    }

    protected function getFooterWidgets(): array
    {
        return [
            SalesKPIsWidget::class,
            LeadsByStatusWidget::class,
            RevenueProfitTrendWidget::class,
        ];
    }

    public function getTabs(): array
    {
        $user = auth()->user();
        $baseQuery = function() use ($user) {
            return MySalesDashboardResource::getEloquentQuery();
        };

        return [
            'all' => Tab::make('All')
                ->badge($baseQuery()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query),

            'open' => Tab::make('Open')
                ->badge($baseQuery()->where('status', LeadStatus::ASSIGNED_TO_SALES->value)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', LeadStatus::ASSIGNED_TO_SALES->value)),

            'info_complete' => Tab::make('Info Complete')
                ->badge($baseQuery()->where('status', LeadStatus::INFO_GATHER_COMPLETE->value)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', LeadStatus::INFO_GATHER_COMPLETE->value)),

            'pricing' => Tab::make('Pricing')
                ->badge($baseQuery()->where('status', LeadStatus::PRICING_IN_PROGRESS->value)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', LeadStatus::PRICING_IN_PROGRESS->value)),

            'sent_customer' => Tab::make('Sent to Customer')
                ->badge($baseQuery()->where('status', LeadStatus::SENT_TO_CUSTOMER->value)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', LeadStatus::SENT_TO_CUSTOMER->value)),

            'confirmed' => Tab::make('Confirmed')
                ->badge($baseQuery()->where('status', LeadStatus::CONFIRMED->value)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', LeadStatus::CONFIRMED->value)),

            'closed' => Tab::make('Closed')
                ->badge($baseQuery()->where('status', LeadStatus::MARK_CLOSED->value)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', LeadStatus::MARK_CLOSED->value)),
        ];
    }
} 