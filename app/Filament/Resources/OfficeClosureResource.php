<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OfficeClosureResource\Pages;
use App\Filament\Resources\OfficeClosureResource\RelationManagers;
use App\Models\OfficeClosure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Enums\ClosureType;

class OfficeClosureResource extends Resource
{
    protected static ?string $model = OfficeClosure::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'HR Management';

    protected static ?string $navigationLabel = 'Office Closures & Holidays';

    protected static ?string $pluralLabel = 'Office Closures & Holidays';

    protected static ?string $modelLabel = 'Office Closure / Holiday';

    public static function canViewAny(): bool
    {
        return auth()->user() && (auth()->user()->isHR() || auth()->user()->isAdmin());
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Closure Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., New Year Holiday, Office Maintenance')
                            ->columnSpanFull(),
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options(ClosureType::getOptions())
                            ->required()
                            ->default(ClosureType::HOLIDAY->value),
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Start Date')
                            ->required()
                            ->default(now()),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('End Date')
                            ->required()
                            ->afterOrEqual('start_date')
                            ->default(now()),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->placeholder('Optional description or notes about this closure')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Forms\Components\Hidden::make('created_by')
                    ->default(auth()->id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'info' => ClosureType::HOLIDAY->value,
                        'danger' => ClosureType::OFFICE_CLOSURE->value,
                    ])
                    ->formatStateUsing(fn ($state) => $state->getLabel()),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Start Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('End Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small),
                Tables\Columns\TextColumn::make('duration_in_days')
                    ->label('Duration')
                    ->suffix(' days')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->placeholder('Unknown')
                    ->color('info'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->since()
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small)
                    ->color('gray'),
            ])
            ->defaultSort('start_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options(ClosureType::getOptions())
                    ->placeholder('All Types'),
                Tables\Filters\Filter::make('date_range')
                    ->label('Date Range')
                    ->form([
                        Forms\Components\DatePicker::make('from_date')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('to_date')
                            ->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '>=', $date),
                            )
                            ->when(
                                $data['to_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('end_date', '<=', $date),
                            );
                    }),
            ])
            ->filtersFormColumns(2)
            ->persistFiltersInSession()
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOfficeClosures::route('/'),
            'create' => Pages\CreateOfficeClosure::route('/create'),
            'view' => Pages\ViewOfficeClosure::route('/{record}'),
            'edit' => Pages\EditOfficeClosure::route('/{record}/edit'),
        ];
    }
}
