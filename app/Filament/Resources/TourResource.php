<?php

namespace App\Filament\Resources;

use App\Enums\TourStatus;
use App\Filament\Resources\TourResource\Pages;
use App\Filament\Resources\TourResource\RelationManagers\LeadsRelationManager;
use App\Models\Tour;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TourResource extends Resource
{
    protected static ?string $model = Tour::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Tour Master';

    protected static ?string $label = 'Tour';

    protected static ?string $pluralLabel = 'Tours';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user && ($user->isAdmin() || $user->isAccount());
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Tour details')
                    ->schema([
                        Forms\Components\TextInput::make('tour_code')
                            ->label('Tour code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->helperText('Auto-generated on create if left blank. Example: CHN-25JUL-2026'),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('departure_date')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                                if (blank($get('tour_code')) && filled($state) && filled($get('name'))) {
                                    $set('tour_code', app(\App\Services\TourCodeGenerator::class)->generate(
                                        null,
                                        \Carbon\Carbon::parse($state),
                                        $get('name')
                                    ));
                                }
                            }),
                        Forms\Components\DatePicker::make('return_date'),
                        Forms\Components\Select::make('status')
                            ->options(TourStatus::options())
                            ->default(TourStatus::Open->value)
                            ->required()
                            ->live(),
                        Forms\Components\TextInput::make('package_price')
                            ->label('Package price per pax')
                            ->numeric()
                            ->prefix('LKR')
                            ->default(0)
                            ->required(),
                        Forms\Components\Select::make('currency')
                            ->options([
                                'LKR' => 'LKR',
                                'USD' => 'USD',
                                'SGD' => 'SGD',
                                'CNY' => 'CNY',
                            ])
                            ->default('LKR')
                            ->required(),
                        Forms\Components\TextInput::make('seat_capacity')
                            ->numeric()
                            ->minValue(1),
                        Forms\Components\TextInput::make('estimated_vendor_cost')
                            ->label('Estimated vendor cost (planning)')
                            ->numeric()
                            ->prefix('LKR'),
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Capacity summary')
                    ->schema([
                        Forms\Components\Placeholder::make('booked_seats_display')
                            ->label('Booked seats')
                            ->content(fn (?Tour $record): string => $record ? (string) $record->booked_seats : '0'),
                        Forms\Components\Placeholder::make('available_seats_display')
                            ->label('Available seats')
                            ->content(fn (?Tour $record): string => $record && $record->available_seats !== null
                                ? (string) $record->available_seats
                                : '—'),
                        Forms\Components\Placeholder::make('expected_sales_display')
                            ->label('Expected sales')
                            ->content(fn (?Tour $record): string => $record
                                ? 'LKR '.number_format($record->expected_sales, 2)
                                : '—'),
                    ])
                    ->columns(3)
                    ->visibleOn(['view', 'edit']),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tour_code')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('departure_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof TourStatus ? $state->label() : TourStatus::tryFrom((string) $state)?->label() ?? $state),
                Tables\Columns\TextColumn::make('booked_seats')
                    ->label('Booked')
                    ->getStateUsing(fn (Tour $record): int => $record->booked_seats),
                Tables\Columns\TextColumn::make('seat_capacity')
                    ->label('Capacity'),
                Tables\Columns\TextColumn::make('package_price')
                    ->money('LKR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('leads_count')
                    ->counts('leads')
                    ->label('Bookings'),
            ])
            ->defaultSort('departure_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(TourStatus::options()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            LeadsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTours::route('/'),
            'create' => Pages\CreateTour::route('/create'),
            'view' => Pages\ViewTour::route('/{record}'),
            'edit' => Pages\EditTour::route('/{record}/edit'),
        ];
    }
}
