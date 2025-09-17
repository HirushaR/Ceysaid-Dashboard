<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AllDepartureResource\Pages;
use App\Models\Lead;
use App\Traits\HasResourcePermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\LeadStatus;
use App\Models\CallCenterCall;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class AllDepartureResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = Lead::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'All Departure';
    protected static ?string $label = 'All Departure';
    protected static ?string $pluralLabel = 'All Departure';
    protected static ?string $navigationGroup = 'Call Center';

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        
        // Admin and call center users can view this resource
        return $user->isCallCenter();
    }

    public static function canCreate(): bool
    {
        return false; // This resource is read-only for leads
    }

    public static function canEdit(Model $record): bool
    {
        return false; // This resource is read-only for leads
    }

    public static function canDelete(Model $record): bool
    {
        return false; // This resource is read-only for leads
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        
        return  $user->isCallCenter();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        // Show confirmed leads where departure_date is 2 days before current date
        // and don't have a pre-departure call assigned yet
        $twoDaysFromNow = Carbon::now()->addDays(2)->format('Y-m-d');
        return $query
            ->where('status', LeadStatus::CONFIRMED->value)
            ->where('depature_date', $twoDaysFromNow)
            ->whereDoesntHave('preDepartureCall');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAllDepartures::route('/'),
            'view' => Pages\ViewAllDeparture::route('/{record}'),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
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
                    
                Tables\Columns\TextColumn::make('arrival_date')
                    ->label('Arrival Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->color('success'),
                    
                Tables\Columns\TextColumn::make('depature_date')
                    ->label('Departure Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->color('warning'),
                    
                Tables\Columns\TextColumn::make('destination')
                    ->label('Destination')
                    ->searchable()
                    ->badge()
                    ->color('info'),
                    
                Tables\Columns\TextColumn::make('number_of_adults')
                    ->label('Adults')
                    ->numeric()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('number_of_children')
                    ->label('Children')
                    ->numeric()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('contact_value')
                    ->label('Contact')
                    ->searchable()
                    ->copyable()
                    ->placeholder('No contact info'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->since()
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small)
                    ->color('gray'),
            ])
            ->defaultSort('depature_date', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('destination')
                    ->options(function () {
                        return Lead::whereNotNull('destination')
                            ->distinct()
                            ->pluck('destination', 'destination')
                            ->toArray();
                    })
                    ->searchable(),
                Tables\Filters\Filter::make('departure_date')
                    ->form([
                        Forms\Components\DatePicker::make('departure_from')
                            ->label('Departure From'),
                        Forms\Components\DatePicker::make('departure_until')
                            ->label('Departure Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['departure_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('depature_date', '>=', $date),
                            )
                            ->when(
                                $data['departure_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('depature_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->button()
                    ->size('sm'),
                Tables\Actions\Action::make('assign_to_me')
                    ->label('Assign to Me')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->action(function (Lead $record) {
                        CallCenterCall::create([
                            'lead_id' => $record->id,
                            'assigned_call_center_user' => auth()->id(),
                            'call_type' => CallCenterCall::CALL_TYPE_PRE_DEPARTURE,
                            'status' => CallCenterCall::STATUS_ASSIGNED,
                        ]);
                    })
                    ->visible(fn () => auth()->user()?->isCallCenter()),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('assign_to_me')
                    ->label('Assign Selected to Me')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->action(function ($records) {
                        $records->each(function (Lead $record) {
                            CallCenterCall::create([
                                'lead_id' => $record->id,
                                'assigned_call_center_user' => auth()->id(),
                                'call_type' => CallCenterCall::CALL_TYPE_PRE_DEPARTURE,
                                'status' => CallCenterCall::STATUS_ASSIGNED,
                            ]);
                        });
                    })
                    ->visible(fn () => auth()->user()?->isCallCenter()),
            ])
            ->recordUrl(fn($record) => static::getUrl('view', ['record' => $record]))
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('customer_name')->label('Customer Name')->disabled(),
                Forms\Components\TextInput::make('destination')->label('Destination')->disabled(),
                Forms\Components\DatePicker::make('arrival_date')->label('Arrival Date')->disabled(),
                Forms\Components\DatePicker::make('depature_date')->label('Departure Date')->disabled(),
                Forms\Components\TextInput::make('number_of_adults')->label('Number of Adults')->disabled(),
                Forms\Components\TextInput::make('number_of_children')->label('Number of Children')->disabled(),
                Forms\Components\TextInput::make('contact_value')->label('Contact')->disabled(),
                Forms\Components\Textarea::make('tour_details')->label('Tour Details')->disabled(),
            ]);
    }
}
