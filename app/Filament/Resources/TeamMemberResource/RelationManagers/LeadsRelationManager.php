<?php

namespace App\Filament\Resources\TeamMemberResource\RelationManagers;

use App\Enums\LeadStatus;
use App\Enums\Priority;
use App\Enums\Platform;
use App\Models\Lead;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LeadsRelationManager extends RelationManager
{
    protected static ?string $title = 'Assigned Leads';

    protected static ?string $recordTitleAttribute = 'reference_id';

    protected static string $relationship = 'leads';

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $ownerRecord = $this->getOwnerRecord();
        
        // For call center users, get leads through call_center_calls
        if ($ownerRecord->isCallCenter()) {
            return Lead::query()->whereHas('callCenterCalls', function ($query) use ($ownerRecord) {
                $query->where('assigned_call_center_user', $ownerRecord->id);
            });
        }
        
        // For operation users, use operatorLeads relationship
        if ($ownerRecord->isOperation()) {
            return $ownerRecord->operatorLeads()->getQuery();
        }
        
        // For sales and other users, use leads relationship
        return $ownerRecord->leads()->getQuery();
    }

    public function form(Form $form): Form
    {
        // Read-only form - managers can only view leads
        return $form
            ->schema([
                Forms\Components\Section::make('Lead Information')
                    ->schema([
                        Forms\Components\TextInput::make('reference_id')
                            ->label('Reference ID')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('customer_name')
                            ->label('Customer Name')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(LeadStatus::options())
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference_id')
            ->columns([
                Tables\Columns\TextColumn::make('reference_id')
                    ->label('Reference ID')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small)
                    ->color('gray'),
                    
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer')
                    ->sortable()
                    ->searchable()
                    ->weight('medium')
                    ->description(fn ($record) => $record->customer?->name ? "System: {$record->customer->name}" : null),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors(LeadStatus::colorMap())
                    ->formatStateUsing(fn ($state) => LeadStatus::tryFrom($state)?->label() ?? $state)
                    ->sortable(),
                    
                Tables\Columns\BadgeColumn::make('priority')
                    ->label('Priority')
                    ->colors(Priority::colorMap())
                    ->formatStateUsing(fn ($state) => Priority::tryFrom($state)?->label() ?? ucfirst($state))
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('platform')
                    ->label('Source')
                    ->badge()
                    ->colors(Platform::colorMap())
                    ->formatStateUsing(fn ($state) => Platform::tryFrom($state)?->label() ?? ucfirst($state))
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('destination')
                    ->label('Destination')
                    ->limit(15)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 15 ? $state : null;
                    })
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('arrival_date')
                    ->label('Travel Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->since()
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small)
                    ->color('gray'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('all_leads')
                    ->label(function () {
                        $owner = $this->getOwnerRecord();
                        $count = $owner->getAllLeads()->count();
                        return 'All (' . $count . ')';
                    })
                    ->indicator('All')
                    ->query(fn (Builder $query): Builder => $query)
                    ->default(),
                    
                Filter::make('open_leads')
                    ->label(function () {
                        $owner = $this->getOwnerRecord();
                        $count = $owner->getAllLeads()->where('status', LeadStatus::ASSIGNED_TO_SALES->value)->count();
                        return 'Open (' . $count . ')';
                    })
                    ->indicator('Open')
                    ->query(fn (Builder $query): Builder => $query->where('status', LeadStatus::ASSIGNED_TO_SALES->value)),
                    
                Filter::make('info_complete_leads')
                    ->label(function () {
                        $owner = $this->getOwnerRecord();
                        $count = $owner->getAllLeads()->where('status', LeadStatus::INFO_GATHER_COMPLETE->value)->count();
                        return 'Info Complete (' . $count . ')';
                    })
                    ->indicator('Info Complete')
                    ->query(fn (Builder $query): Builder => $query->where('status', LeadStatus::INFO_GATHER_COMPLETE->value)),
                    
                Filter::make('pricing_leads')
                    ->label(function () {
                        $owner = $this->getOwnerRecord();
                        $count = $owner->getAllLeads()->where('status', LeadStatus::PRICING_IN_PROGRESS->value)->count();
                        return 'Pricing (' . $count . ')';
                    })
                    ->indicator('Pricing')
                    ->query(fn (Builder $query): Builder => $query->where('status', LeadStatus::PRICING_IN_PROGRESS->value)),
                    
                Filter::make('sent_to_customer_leads')
                    ->label(function () {
                        $owner = $this->getOwnerRecord();
                        $count = $owner->getAllLeads()->where('status', LeadStatus::SENT_TO_CUSTOMER->value)->count();
                        return 'Sent to Customer (' . $count . ')';
                    })
                    ->indicator('Sent to Customer')
                    ->query(fn (Builder $query): Builder => $query->where('status', LeadStatus::SENT_TO_CUSTOMER->value)),
                    
                Filter::make('confirmed_leads')
                    ->label(function () {
                        $owner = $this->getOwnerRecord();
                        $count = $owner->getAllLeads()->where('status', LeadStatus::CONFIRMED->value)->count();
                        return 'Confirmed (' . $count . ')';
                    })
                    ->indicator('Confirmed')
                    ->query(fn (Builder $query): Builder => $query->where('status', LeadStatus::CONFIRMED->value)),
                    
                Filter::make('closed_leads')
                    ->label(function () {
                        $owner = $this->getOwnerRecord();
                        $count = $owner->getAllLeads()->where('status', LeadStatus::MARK_CLOSED->value)->count();
                        return 'Closed (' . $count . ')';
                    })
                    ->indicator('Closed')
                    ->query(fn (Builder $query): Builder => $query->where('status', LeadStatus::MARK_CLOSED->value)),
                    
                Tables\Filters\SelectFilter::make('status')
                    ->options(LeadStatus::options())
                    ->label('Lead Status (Detailed)'),
                    
                Tables\Filters\SelectFilter::make('priority')
                    ->options(Priority::options())
                    ->label('Priority'),
                    
                Tables\Filters\SelectFilter::make('platform')
                    ->options(Platform::options())
                    ->label('Platform'),
                    
                Tables\Filters\Filter::make('active_leads')
                    ->label('Active Leads Only')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereNotIn('status', [
                        LeadStatus::MARK_CLOSED->value,
                        LeadStatus::OPERATION_COMPLETE->value,
                        LeadStatus::DOCUMENT_UPLOAD_COMPLETE->value
                    ])),
                    
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Created From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Created Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
                    
                Tables\Filters\Filter::make('arrival_date')
                    ->form([
                        Forms\Components\DatePicker::make('arrival_from')
                            ->label('Arrival From'),
                        Forms\Components\DatePicker::make('arrival_until')
                            ->label('Arrival Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['arrival_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('arrival_date', '>=', $date),
                            )
                            ->when(
                                $data['arrival_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('arrival_date', '<=', $date),
                            );
                    }),
                    
                Tables\Filters\TrashedFilter::make()
                    ->label('Include Deleted'),
            ])
            ->headerActions([
                // No create action - managers can't create leads through this interface
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->button()
                    ->size('sm')
                    ->url(fn ($record) => \App\Filament\Resources\LeadResource::getUrl('view', ['record' => $record])),
                // No edit/delete actions - managers can only view leads
            ])
            ->bulkActions([
                // No bulk actions - managers can't modify leads
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]))
            ->emptyStateHeading('No Leads Assigned')
            ->emptyStateDescription('This team member has no assigned leads yet.')
            ->emptyStateIcon('heroicon-o-document-text');
    }
}
