<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CallCenterCallResource\Pages;
use App\Models\CallCenterCall;
use App\Traits\HasResourcePermissions;
use App\Enums\ServiceStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CallCenterCallResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = CallCenterCall::class;
    protected static ?string $navigationIcon = 'heroicon-o-phone';
    protected static ?string $navigationLabel = 'My Assigned Calls';
    protected static ?string $label = 'My Assigned Call';
    protected static ?string $pluralLabel = 'My Assigned Calls';
    protected static ?string $navigationGroup = 'Call Center';

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        
        return $user->isAdmin() || $user->isCallCenter();
    }

    public static function canCreate(): bool
    {
        return false; // Calls are created through assignment actions
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        
        if ($user->isAdmin()) return true;
        
        return $user->isCallCenter() && $record->assigned_call_center_user === $user->id;
    }

    public static function canDelete(Model $record): bool
    {
        return false; // Calls should not be deleted
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        
        if ($user->isAdmin()) return true;
        
        return $user->isCallCenter() && $record->assigned_call_center_user === $user->id;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();
        
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }
        
        if ($user->isAdmin()) {
            return $query;
        }
        
        return $query->where('assigned_call_center_user', $user->id);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCallCenterCalls::route('/'),
            'view' => Pages\ViewCallCenterCall::route('/{record}'),
            'edit' => Pages\EditCallCenterCall::route('/{record}/edit'),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('lead.reference_id')
                    ->label('Reference ID')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small)
                    ->color('gray'),
                    
                Tables\Columns\TextColumn::make('lead.customer_name')
                    ->label('Customer')
                    ->sortable()
                    ->searchable()
                    ->weight('medium'),
                    
                Tables\Columns\BadgeColumn::make('call_type')
                    ->label('Call Type')
                    ->formatStateUsing(fn ($state) => CallCenterCall::getCallTypes()[$state] ?? $state)
                    ->colors([
                        'info' => CallCenterCall::CALL_TYPE_PRE_DEPARTURE,
                        'success' => CallCenterCall::CALL_TYPE_POST_ARRIVAL,
                    ]),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => CallCenterCall::getStatuses()[$state] ?? $state)
                    ->colors([
                        'gray' => CallCenterCall::STATUS_PENDING,
                        'warning' => CallCenterCall::STATUS_ASSIGNED,
                        'info' => CallCenterCall::STATUS_CALLED,
                        'danger' => CallCenterCall::STATUS_NOT_ANSWERED,
                        'success' => CallCenterCall::STATUS_COMPLETED,
                    ]),
                    
                Tables\Columns\TextColumn::make('lead.arrival_date')
                    ->label('Arrival Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->color('success'),
                    
                Tables\Columns\TextColumn::make('lead.depature_date')
                    ->label('Departure Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->color('warning'),
                    
                Tables\Columns\TextColumn::make('lead.destination')
                    ->label('Destination')
                    ->searchable()
                    ->badge()
                    ->color('info'),
                    
                Tables\Columns\TextColumn::make('call_attempts')
                    ->label('Call Attempts')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray'),
                    
                Tables\Columns\TextColumn::make('last_call_attempt')
                    ->label('Last Call')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->since()
                    ->placeholder('Never called')
                    ->color('gray'),
                    
                Tables\Columns\BadgeColumn::make('lead.air_ticket_status')
                    ->label('Air Ticket')
                    ->formatStateUsing(fn ($state) => ServiceStatus::tryFrom($state ?? 'pending')?->getLabel() ?? 'Pending')
                    ->colors(ServiceStatus::colorMap()),
                    
                Tables\Columns\BadgeColumn::make('lead.hotel_status')
                    ->label('Hotel')
                    ->formatStateUsing(fn ($state) => ServiceStatus::tryFrom($state ?? 'pending')?->getLabel() ?? 'Pending')
                    ->colors(ServiceStatus::colorMap()),
                    
                Tables\Columns\BadgeColumn::make('lead.visa_status')
                    ->label('Visa')
                    ->formatStateUsing(fn ($state) => ServiceStatus::tryFrom($state ?? 'pending')?->getLabel() ?? 'Pending')
                    ->colors(ServiceStatus::colorMap()),
                    
                Tables\Columns\BadgeColumn::make('lead.land_package_status')
                    ->label('Land Package')
                    ->formatStateUsing(fn ($state) => ServiceStatus::tryFrom($state ?? 'pending')?->getLabel() ?? 'Pending')
                    ->colors(ServiceStatus::colorMap()),
            ])
            ->defaultSort('status', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('call_type')
                    ->options(CallCenterCall::getCallTypes())
                    ->label('Call Type'),
                Tables\Filters\SelectFilter::make('status')
                    ->options(CallCenterCall::getStatuses())
                    ->label('Status'),
                Tables\Filters\SelectFilter::make('lead.destination')
                    ->options(function () {
                        return \App\Models\Lead::whereNotNull('destination')
                            ->distinct()
                            ->pluck('destination', 'destination')
                            ->toArray();
                    })
                    ->searchable()
                    ->label('Destination'),
                Tables\Filters\SelectFilter::make('lead.air_ticket_status')
                    ->options(ServiceStatus::options())
                    ->label('Air Ticket Status'),
                Tables\Filters\SelectFilter::make('lead.hotel_status')
                    ->options(ServiceStatus::options())
                    ->label('Hotel Status'),
                Tables\Filters\SelectFilter::make('lead.visa_status')
                    ->options(ServiceStatus::options())
                    ->label('Visa Status'),
                Tables\Filters\SelectFilter::make('lead.land_package_status')
                    ->options(ServiceStatus::options())
                    ->label('Land Package Status'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->button()
                    ->size('sm'),
                Tables\Actions\EditAction::make()
                    ->button()
                    ->size('sm')
                    ->color('gray'),
            ])
            ->recordUrl(fn($record) => static::getUrl('view', ['record' => $record]))
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Lead Information Section
                Forms\Components\Section::make('Lead Information')
                    ->schema([
                        Forms\Components\TextInput::make('lead.customer_name')
                            ->label('Customer Name')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('lead.destination')
                            ->label('Destination')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\DatePicker::make('lead.arrival_date')
                            ->label('Arrival Date')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\DatePicker::make('lead.depature_date')
                            ->label('Departure Date')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('lead.number_of_adults')
                            ->label('Number of Adults')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('lead.number_of_children')
                            ->label('Number of Children')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('lead.contact_value')
                            ->label('Contact')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Textarea::make('lead.tour_details')
                            ->label('Tour Details')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->collapsed(false)
                    ->compact(),

                    Forms\Components\Section::make('Service Status')
                    ->schema([
                        Forms\Components\TextInput::make('lead.air_ticket_status')
                            ->label('Air Ticket Status')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(function ($record) {
                                $status = $record?->lead?->air_ticket_status ?? 'pending';
                                return ServiceStatus::tryFrom($status)?->getLabel() ?? 'Pending';
                            }),
                        Forms\Components\TextInput::make('lead.hotel_status')
                            ->label('Hotel Status')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(function ($record) {
                                $status = $record?->lead?->hotel_status ?? 'pending';
                                return ServiceStatus::tryFrom($status)?->getLabel() ?? 'Pending';
                            }),
                        Forms\Components\TextInput::make('lead.visa_status')
                            ->label('Visa Status')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(function ($record) {
                                $status = $record?->lead?->visa_status ?? 'pending';
                                return ServiceStatus::tryFrom($status)?->getLabel() ?? 'Pending';
                            }),
                        Forms\Components\TextInput::make('lead.land_package_status')
                            ->label('Land Package Status')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(function ($record) {
                                $status = $record?->lead?->land_package_status ?? 'pending';
                                return ServiceStatus::tryFrom($status)?->getLabel() ?? 'Pending';
                            }),
                    ])
                    ->columns(2)
                    ->collapsible(),


                                // Call Checklist Section
                                Forms\Components\Section::make('Call Checklist')
                                ->schema([
                                    Forms\Components\CheckboxList::make('call_checklist_completed')
                                        ->label('Checklist Items')
                                        ->options(function ($record) {
                                            $callType = $record?->call_type ?? 'pre_departure';
                                            
                                            if ($callType === 'post_arrival') {
                                                // Post-Arrival Call Checklist
                                                return [
                                                    'confirmed_safe_arrival' => 'Confirmed safe arrival',
                                                    'confirmed_accommodation' => 'Confirmed accommodation details',
                                                    'confirmed_pickup_arrangement' => 'Confirmed pickup arrangement',
                                                    'provided_local_contact' => 'Provided local contact information',
                                                    'confirmed_tour_schedule' => 'Confirmed tour schedule',
                                                    'reminded_emergency_procedures' => 'Reminded about emergency procedures',
                                                    'confirmed_special_needs' => 'Confirmed any special needs',
                                                    'provided_destination_tips' => 'Provided destination tips and information',
                                                    'confirmed_next_contact' => 'Confirmed next contact time',
                                                    'addressed_concerns' => 'Addressed any immediate concerns',
                                                    'confirmed_documentation' => 'Confirmed all documentation is in order',
                                                    'provided_weather_info' => 'Provided weather information',
                                                ];
                                            } else {
                                                // Pre-Departure Call Checklist
                                                return [
                                                    'confirmed_departure_details' => 'Confirmed departure details',
                                                    'confirmed_passenger_count' => 'Confirmed passenger count',
                                                    'confirmed_contact_info' => 'Confirmed contact information',
                                                    'reminded_documents' => 'Reminded about required documents',
                                                    'reminded_visa' => 'Reminded about visa requirements',
                                                    'reminded_insurance' => 'Reminded about travel insurance',
                                                    'reminded_currency' => 'Reminded about currency exchange',
                                                    'confirmed_pickup' => 'Confirmed pickup arrangements',
                                                    'confirmed_hotel' => 'Confirmed hotel details',
                                                    'provided_emergency_contact' => 'Provided emergency contact information',
                                                    'confirmed_special_requirements' => 'Confirmed any special requirements',
                                                    'reminded_packing_list' => 'Reminded about packing essentials',
                                                ];
                                            }
                                        })
                                        ->columns(2)
                                        ->helperText(function ($record) {
                                            $callType = $record?->call_type ?? 'pre_departure';
                                            return $callType === 'post_arrival' 
                                                ? 'Check all items that were completed during the post-arrival call'
                                                : 'Check all items that were completed during the pre-departure call';
                                        }),
                                ])
                                ->collapsed(false)
                                ->compact(),
                
                    // Call Management Section
                Forms\Components\Section::make('Call Management')
                    ->schema([
                        Forms\Components\Select::make('call_type')
                            ->label('Call Type')
                            ->options(CallCenterCall::getCallTypes())
                            ->disabled(),
                        
                        Forms\Components\Select::make('status')
                            ->label('Call Status')
                            ->options(CallCenterCall::getStatuses())
                            ->required()
                            ->native(false),
                        
                        Forms\Components\Textarea::make('call_notes')
                            ->label('Call Notes')
                            ->rows(4)
                            ->placeholder('Enter call details, customer responses, and any important information...'),
                        
                        Forms\Components\TextInput::make('call_attempts')
                            ->label('Call Attempts')
                            ->numeric()
                            ->disabled()
                            ->helperText('Automatically updated when call status changes'),
                        
                        Forms\Components\DateTimePicker::make('last_call_attempt')
                            ->label('Last Call Attempt')
                            ->disabled()
                            ->helperText('Automatically updated when call status changes'),
                    ])
                    ->collapsed(false)
                    ->compact(),


            ]);
    }
}
