<?php

namespace App\Filament\Resources;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Filament\Resources\MyCallCenterLeadResource\Pages;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MyCallCenterLeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static ?string $navigationIcon = 'heroicon-o-phone';

    protected static ?string $navigationLabel = 'My Leads';

    protected static ?string $modelLabel = 'Lead';

    protected static ?string $pluralModelLabel = 'My Leads';

    protected static ?string $navigationGroup = 'Dashboard';

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        return parent::getEloquentQuery()
            ->notArchived()
            ->where('created_by', $user ? $user->id : 0);
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && $user->isCallCenter();
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        return $user && $user->isCallCenter();
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();
        return $user && $user->isCallCenter() && $record->created_by === $user->id;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMyCallCenterLeads::route('/'),
            'create' => Pages\CreateMyCallCenterLead::route('/create'),
            'view' => Pages\ViewMyCallCenterLead::route('/{record}'),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema(LeadResource::getFormSchema())
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_id')
                    ->label('Reference ID')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn ($state, $record) => $state ?: "ID: {$record->id}"),
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('contact_value')
                    ->label('Contact')
                    ->limit(20)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors(LeadStatus::colorMap())
                    ->formatStateUsing(fn ($state) => LeadStatus::tryFrom($state)?->label() ?? $state),
                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('Assigned To')
                    ->placeholder('Unassigned'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(LeadStatus::options())
                    ->label('Status'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->recordUrl(fn ($record) => static::getUrl('view', ['record' => $record]))
            ->striped()
            ->paginated([10, 25, 50]);
    }
}
