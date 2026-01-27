<?php

namespace App\Filament\Resources;

use App\Models\Lead;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ArchiveLeadResource\Pages;
use App\Enums\LeadStatus;
use App\Enums\Platform;

class ArchiveLeadResource extends Resource
{
    protected static ?string $model = Lead::class;
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationLabel = 'Archive Leads';
    protected static ?string $label = 'Archive Lead';
    protected static ?string $pluralLabel = 'Archive Leads';
    protected static ?string $navigationGroup = 'Dashboard';

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        
        // Only admin users can view archived leads
        return $user->isAdmin();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNotNull('archived_at')
            ->whereNull('deleted_at');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArchiveLeads::route('/'),
            'view' => Pages\ViewArchiveLead::route('/{record}'),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small)
                    ->color('primary')
                    ->weight('bold'),
                    
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
                    ->weight('medium'),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors(LeadStatus::colorMap())
                    ->formatStateUsing(fn ($state) => LeadStatus::tryFrom($state)?->label() ?? $state),
                    
                Tables\Columns\TextColumn::make('priority')
                    ->label('Priority')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'low' => 'gray',
                        'medium' => 'warning',
                        'high' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucfirst($state ?? 'medium')),
                    
                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('Sales Rep')
                    ->sortable()
                    ->searchable()
                    ->placeholder('Unassigned')
                    ->color('info'),
                    
                Tables\Columns\TextColumn::make('assignedOperator.name')
                    ->label('Operator')
                    ->sortable()
                    ->searchable()
                    ->placeholder('Unassigned')
                    ->color('warning'),
                    
                Tables\Columns\TextColumn::make('platform')
                    ->label('Source')
                    ->badge()
                    ->colors(Platform::colorMap())
                    ->formatStateUsing(fn ($state) => Platform::tryFrom($state)?->label() ?? ucfirst($state)),
                    
                Tables\Columns\TextColumn::make('archivedBy.name')
                    ->label('Archived By')
                    ->sortable()
                    ->color('gray'),
                    
                Tables\Columns\TextColumn::make('archived_at')
                    ->label('Archived At')
                    ->dateTime('M j, Y \a\t g:i A')
                    ->sortable()
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small)
                    ->color('gray'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->since()
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small)
                    ->color('gray'),
            ])
            ->defaultSort('archived_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(LeadStatus::options())
                    ->label('Lead Status'),
                Tables\Filters\SelectFilter::make('platform')
                    ->options(Platform::options())
                    ->label('Platform'),
                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                    ])
                    ->label('Priority'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->button()
                    ->size('sm'),
                Tables\Actions\Action::make('unarchive')
                    ->label('Unarchive')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->button()
                    ->size('sm')
                    ->requiresConfirmation()
                    ->modalHeading('Unarchive Lead')
                    ->modalDescription('Are you sure you want to unarchive this lead? It will become visible in other dashboards again.')
                    ->action(function ($record) {
                        $user = auth()->user();
                        $record->archived_at = null;
                        $record->archived_by = null;
                        $record->save();
                        
                        // Log unarchive action
                        \App\Models\LeadActionLog::create([
                            'lead_id' => $record->id,
                            'user_id' => $user->id,
                            'action' => 'unarchived',
                            'description' => 'Lead unarchived',
                        ]);
                        
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Lead unarchived successfully.')
                            ->send();
                    }),
            ])
            ->recordUrl(fn($record) => static::getUrl('view', ['record' => $record]))
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
