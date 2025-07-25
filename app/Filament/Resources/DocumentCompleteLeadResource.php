<?php

namespace App\Filament\Resources;

use App\Models\Lead;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\DocumentCompleteLeadResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use App\Enums\LeadStatus;
use App\Enums\ServiceStatus;

class DocumentCompleteLeadResource extends Resource
{
    protected static ?string $model = Lead::class;
    protected static ?string $navigationIcon = 'heroicon-o-identification';
    protected static ?string $navigationLabel = 'Visa Leads';
    protected static ?string $label = 'Visa Lead';
    protected static ?string $pluralLabel = 'Visa Leads';
    protected static ?string $navigationGroup = 'Dashboard';

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && ($user->isSales() || $user->isOperation() || $user->isAdmin());
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('status', LeadStatus::DOCUMENT_UPLOAD_COMPLETE->value);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocumentCompleteLeads::route('/'),
            'view' => Pages\ViewDocumentCompleteLead::route('/{record}'),
            'edit' => Pages\EditDocumentCompleteLead::route('/{record}/edit'),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_id')->label('Reference ID')->sortable(),
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('customer_name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('customer.name')->label('Customer')->sortable()->searchable(),
                Tables\Columns\BadgeColumn::make('platform')
                    ->colors([
                        'facebook' => 'info',
                        'whatsapp' => 'success',
                        'email' => 'warning',
                    ]),
                Tables\Columns\TextColumn::make('tour')->limit(20),
                Tables\Columns\TextColumn::make('destination'),
                Tables\Columns\TextColumn::make('country'),
                Tables\Columns\TextColumn::make('assignedUser.name')->label('Assigned To')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('assignedOperator.name')->label('Assigned Operator')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('status')
                ->badge()
                ->color(fn (string $state): string => 
                    LeadStatus::tryFrom($state)?->color() ?? 'secondary'
                ),
                Tables\Columns\IconColumn::make('air_ticket_status')
                    ->label('Air Ticket')
                    ->icon(fn (string $state): string => match ($state) {
                        'pending' => 'heroicon-o-clock',
                        'not_required' => 'heroicon-o-minus-circle',
                        'done' => 'heroicon-o-check-circle',
                        default => 'heroicon-o-question-mark-circle'
                    })
                    ->color(fn (string $state): string => 
                        ServiceStatus::tryFrom($state)?->color() ?? 'gray'
                    )
                    ->size(Tables\Columns\IconColumn\IconColumnSize::Medium),
                Tables\Columns\IconColumn::make('hotel_status')
                    ->label('Hotel')
                    ->icon(fn (string $state): string => match ($state) {
                        'pending' => 'heroicon-o-clock',
                        'not_required' => 'heroicon-o-minus-circle',
                        'done' => 'heroicon-o-check-circle',
                        default => 'heroicon-o-question-mark-circle'
                    })
                    ->color(fn (string $state): string => 
                        ServiceStatus::tryFrom($state)?->color() ?? 'gray'
                    )
                    ->size(Tables\Columns\IconColumn\IconColumnSize::Medium),
                Tables\Columns\IconColumn::make('visa_status')
                    ->label('Visa')
                    ->icon(fn (string $state): string => match ($state) {
                        'pending' => 'heroicon-o-clock',
                        'not_required' => 'heroicon-o-minus-circle',
                        'done' => 'heroicon-o-check-circle',
                        default => 'heroicon-o-question-mark-circle'
                    })
                    ->color(fn (string $state): string => 
                        ServiceStatus::tryFrom($state)?->color() ?? 'gray'
                    )
                    ->size(Tables\Columns\IconColumn\IconColumnSize::Medium),
                Tables\Columns\IconColumn::make('land_package_status')
                    ->label('Land Package')
                    ->icon(fn (string $state): string => match ($state) {
                        'pending' => 'heroicon-o-clock',
                        'not_required' => 'heroicon-o-minus-circle',
                        'done' => 'heroicon-o-check-circle',
                        default => 'heroicon-o-question-mark-circle'
                    })
                    ->color(fn (string $state): string => 
                        ServiceStatus::tryFrom($state)?->color() ?? 'gray'
                    )
                    ->size(Tables\Columns\IconColumn\IconColumnSize::Medium),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(LeadStatus::options())
                    ->label('Lead Status'),
                Tables\Filters\SelectFilter::make('visa_status')
                    ->label('Visa Status')
                    ->options(ServiceStatus::options()),
                Tables\Filters\SelectFilter::make('air_ticket_status')
                    ->options(ServiceStatus::options())
                    ->label('Air Ticket Status'),
                Tables\Filters\SelectFilter::make('hotel_status')
                    ->options(ServiceStatus::options())
                    ->label('Hotel Status'),
                Tables\Filters\SelectFilter::make('land_package_status')
                    ->options(ServiceStatus::options())
                    ->label('Land Package Status'),
                Tables\Filters\SelectFilter::make('platform')
                    ->options([
                        'facebook' => 'Facebook',
                        'whatsapp' => 'WhatsApp',
                        'email' => 'Email',
                    ])
                    ->label('Platform'),
                Tables\Filters\SelectFilter::make('assigned_to')
                    ->relationship('assignedUser', 'name')
                    ->label('Assigned To')
                    ->searchable(),
                Tables\Filters\SelectFilter::make('assigned_operator')
                    ->label('Assigned Operator')
                    ->relationship('assignedOperator', 'name')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn() => auth()->user()?->isSales() || auth()->user()?->isOperation() || auth()->user()?->isAdmin()),
            ])
            ->recordUrl(fn($record) => static::getUrl('view', ['record' => $record]));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Lead Information')
                    ->schema([
                        Forms\Components\TextInput::make('reference_id')->label('Reference ID')->disabled(),
                        Forms\Components\TextInput::make('customer_name')->label('Customer Name')->disabled(),
                        Forms\Components\Select::make('customer_id')
                            ->label('Customer')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->disabled(),
                        Forms\Components\TextInput::make('platform')->label('Platform')->disabled(),
                        Forms\Components\Textarea::make('tour')->label('Tour')->disabled(),
                        Forms\Components\Textarea::make('message')->label('Message')->disabled(),
                        Forms\Components\TextInput::make('country')->label('Country')->disabled(),
                        Forms\Components\TextInput::make('destination')->label('Destination')->disabled(),
                        Forms\Components\DatePicker::make('arrival_date')->label('Arrival Date')->disabled(),
                        Forms\Components\DatePicker::make('depature_date')->label('Departure Date')->disabled(),
                        Forms\Components\TextInput::make('number_of_days')->label('Number of Days')->disabled(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Status Management')
                    ->schema([
                        Forms\Components\Placeholder::make('current_status')
                            ->label('Current Status')
                            ->content(fn($record) => LeadStatus::tryFrom($record->status)?->label() ?? $record->status ?? ''),

                        Forms\Components\Select::make('visa_status')
                            ->label('Visa Status')
                            ->options(ServiceStatus::options())
                            ->default('pending')
                            ->disabled(fn() => !(auth()->user()?->isSales() || auth()->user()?->isOperation() || auth()->user()?->isAdmin()))
                            ->suffixIcon(fn ($state) => match ($state) {
                                'pending' => 'heroicon-o-clock',
                                'not_required' => 'heroicon-o-minus-circle',
                                'done' => 'heroicon-o-check-circle',
                                default => 'heroicon-o-question-mark-circle'
                            })
                            ->suffixIconColor(fn ($state) => match ($state) {
                                'pending' => 'warning',
                                'not_required' => 'gray',
                                'done' => 'success',
                                default => 'gray'
                            })
                            ->helperText('Visa status can ONLY be edited here in the Visa Leads tab'),

                        Forms\Components\Select::make('air_ticket_status')
                            ->label('Air Ticket Status')
                            ->options(ServiceStatus::options())
                            ->default('pending')
                            ->disabled()
                            ->suffixIcon(fn ($state) => match ($state) {
                                'pending' => 'heroicon-o-clock',
                                'not_required' => 'heroicon-o-minus-circle',
                                'done' => 'heroicon-o-check-circle',
                                default => 'heroicon-o-question-mark-circle'
                            })
                            ->suffixIconColor(fn ($state) => match ($state) {
                                'pending' => 'warning',
                                'not_required' => 'gray',
                                'done' => 'success',
                                default => 'gray'
                            }),

                        Forms\Components\Select::make('hotel_status')
                            ->label('Hotel Status')
                            ->options(ServiceStatus::options())
                            ->default('pending')
                            ->disabled()
                            ->suffixIcon(fn ($state) => match ($state) {
                                'pending' => 'heroicon-o-clock',
                                'not_required' => 'heroicon-o-minus-circle',
                                'done' => 'heroicon-o-check-circle',
                                default => 'heroicon-o-question-mark-circle'
                            })
                            ->suffixIconColor(fn ($state) => match ($state) {
                                'pending' => 'warning',
                                'not_required' => 'gray',
                                'done' => 'success',
                                default => 'gray'
                            }),

                        Forms\Components\Select::make('land_package_status')
                            ->label('Land Package Status')
                            ->options(ServiceStatus::options())
                            ->default('pending')
                            ->disabled()
                            ->suffixIcon(fn ($state) => match ($state) {
                                'pending' => 'heroicon-o-clock',
                                'not_required' => 'heroicon-o-minus-circle',
                                'done' => 'heroicon-o-check-circle',
                                default => 'heroicon-o-question-mark-circle'
                            })
                            ->suffixIconColor(fn ($state) => match ($state) {
                                'pending' => 'warning',
                                'not_required' => 'gray',
                                'done' => 'success',
                                default => 'gray'
                            }),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Attachments')
                    ->schema([
                        Forms\Components\Repeater::make('attachments')
                            ->relationship('attachments')
                            ->schema([
                                Forms\Components\TextInput::make('type')
                                    ->label('Document Type')
                                    ->disabled(),
                                Forms\Components\FileUpload::make('file_path')
                                    ->label('Attachment')
                                    ->disk('lead-attachments')
                                    ->directory('')
                                    ->preserveFilenames()
                                    ->downloadable()
                                    ->openable()
                                    ->disabled(),
                            ])
                            ->disabled()
                            ->label('Uploaded Documents')
                            ->columns(2),
                    ])
                    ->collapsible(),

                Forms\Components\DateTimePicker::make('created_at')->label('Created At')->disabled(),
                Forms\Components\DateTimePicker::make('updated_at')->label('Updated At')->disabled(),
            ]);
    }
} 