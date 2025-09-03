<?php

namespace App\Filament\Resources\AllLeadDashboardResource\Pages;

use App\Filament\Resources\AllLeadDashboardResource;
use App\Filament\Resources\LeadResource;
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
        return [];
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

            'assigned_to_sales' => Tab::make('Assigned to Sales')
                ->badge($baseQuery()->where('status', LeadStatus::ASSIGNED_TO_SALES->value)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', LeadStatus::ASSIGNED_TO_SALES->value)),

            'assigned_to_operations' => Tab::make('Assigned to Operations')
                ->badge($baseQuery()->where('status', LeadStatus::ASSIGNED_TO_OPERATIONS->value)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', LeadStatus::ASSIGNED_TO_OPERATIONS->value)),

            'info_gather_complete' => Tab::make('Info Gather Complete')
                ->badge($baseQuery()->where('status', LeadStatus::INFO_GATHER_COMPLETE->value)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', LeadStatus::INFO_GATHER_COMPLETE->value)),
         
            'pricing_in_progress' => Tab::make('Pricing In Progress')
                ->badge($baseQuery()->where('status', LeadStatus::PRICING_IN_PROGRESS->value)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', LeadStatus::PRICING_IN_PROGRESS->value)),

            'sent_to_customer' => Tab::make('Sent to Customer')
                ->badge($baseQuery()->where('status', LeadStatus::SENT_TO_CUSTOMER->value)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', LeadStatus::SENT_TO_CUSTOMER->value)),

            'operation_complete' => Tab::make('Operation Complete')
                ->badge($baseQuery()->where('status', LeadStatus::OPERATION_COMPLETE->value)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', LeadStatus::OPERATION_COMPLETE->value)),

            'confirmed' => Tab::make('Confirmed')
                ->badge($baseQuery()->where('status', LeadStatus::CONFIRMED->value)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', LeadStatus::CONFIRMED->value)),

            'document_upload_complete' => Tab::make('Document Upload Complete')
                ->badge($baseQuery()->where('status', LeadStatus::DOCUMENT_UPLOAD_COMPLETE->value)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', LeadStatus::DOCUMENT_UPLOAD_COMPLETE->value)),
        ];
    }
} 