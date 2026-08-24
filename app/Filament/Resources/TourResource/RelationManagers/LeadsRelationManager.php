<?php

namespace App\Filament\Resources\TourResource\RelationManagers;

use App\Enums\LeadStatus;
use App\Filament\Resources\GroupLeadResource;
use App\Filament\Resources\LeadResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class LeadsRelationManager extends RelationManager
{
    protected static string $relationship = 'leads';

    protected static ?string $title = 'Group bookings';

    protected static ?string $recordTitleAttribute = 'customer_name';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_id')
                    ->label('Ref')
                    ->formatStateUsing(fn ($state, $record) => $record->is_group_lead ? "GL-{$record->id}" : (string) $state),
                Tables\Columns\TextColumn::make('customer_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('booked_pax')
                    ->label('Pax'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => LeadStatus::tryFrom((string) $state)?->label() ?? $state),
                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('Salesperson'),
                Tables\Columns\TextColumn::make('invoices_sum_total_amount')
                    ->sum('invoices', 'total_amount')
                    ->money('LKR')
                    ->label('Invoiced'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => $record->is_group_lead
                        ? GroupLeadResource::getUrl('view', ['record' => $record])
                        : LeadResource::getUrl('view', ['record' => $record])),
            ])
            ->emptyStateHeading('No bookings linked')
            ->emptyStateDescription('Assign group leads to this tour from the lead form.');
    }
}
