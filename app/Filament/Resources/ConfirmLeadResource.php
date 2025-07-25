<?php

namespace App\Filament\Resources;

use App\Models\Lead;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ConfirmLeadResource\Pages;
use App\Filament\Resources\ConfirmLeadResource\RelationManagers;
use Filament\Forms;
use Filament\Forms\Form;
use App\Enums\LeadStatus;
use App\Enums\ServiceStatus;

class ConfirmLeadResource extends Resource
{
    protected static ?string $model = Lead::class;
    protected static ?string $navigationIcon = 'heroicon-o-check-circle';
    protected static ?string $navigationLabel = 'Confirm Lead';
    protected static ?string $label = 'Confirm Lead';
    protected static ?string $pluralLabel = 'Confirm Leads';
    protected static ?string $navigationGroup = 'Dashboard';

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && ($user->isSales() || $user->isOperation() || $user->isAdmin());
    }

    private static function getAttachmentFileUpload(): Forms\Components\FileUpload
    {
        return Forms\Components\FileUpload::make('file_path')
            ->label('Attachment')
            ->disk('lead-attachments')
            ->directory('')
            ->preserveFilenames()
            ->downloadable()
            ->openable()
            ->acceptedFileTypes(['image/*', 'application/pdf', '.doc', '.docx', '.txt'])
            ->maxSize(10 * 1024) // 10MB limit
            ->required()
            ->saveUploadedFileUsing(function ($file, $record, $set) {
                // Generate unique filename to prevent conflicts
                $timestamp = now()->format('Y-m-d_H-i-s');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $fileName = "{$timestamp}_{$originalName}.{$extension}";
                
                $path = $file->storeAs('', $fileName, 'lead-attachments');
                $set('file_path', $path);
                $set('original_name', $file->getClientOriginalName());
                return $path;
            });
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereIn('status', [LeadStatus::CONFIRMED->value, LeadStatus::DOCUMENT_UPLOAD_COMPLETE->value]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\LeadCostsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConfirmLeads::route('/'),
            'view' => Pages\ViewConfirmLead::route('/{record}'),
            'edit' => Pages\EditConfirmLead::route('/{record}/edit'),
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
                Tables\Columns\TextColumn::make('message')->limit(20),
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
                Tables\Filters\SelectFilter::make('air_ticket_status')
                    ->options(ServiceStatus::options())
                    ->label('Air Ticket Status'),
                Tables\Filters\SelectFilter::make('hotel_status')
                    ->options(ServiceStatus::options())
                    ->label('Hotel Status'),
                Tables\Filters\SelectFilter::make('visa_status')
                    ->options(ServiceStatus::options())
                    ->label('Visa Status'),
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
                    ->relationship('assignedOperator', 'name')
                    ->label('Assigned Operator')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
                        Forms\Components\TextInput::make('customer_name')->label('Customer Name')->disabled(fn($context) => $context === 'view'),
                        Forms\Components\Select::make('customer_id')
                            ->label('Customer')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')->required(),
                                Forms\Components\Textarea::make('contact_info')->label('Contact Info'),
                            ])
                            ->disabled(fn($context) => $context === 'view'),
                        Forms\Components\TextInput::make('platform')->label('Platform')->disabled(fn($context) => $context === 'view'),
                        Forms\Components\Textarea::make('tour')->label('Tour')->disabled(fn($context) => $context === 'view'),
                        Forms\Components\Textarea::make('message')->label('Message')->disabled(fn($context) => $context === 'view'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\TextInput::make('contact_method')->label('Contact Method')->disabled(fn($context) => $context === 'view'),
                        Forms\Components\TextInput::make('contact_value')->label('Contact Value')->disabled(fn($context) => $context === 'view'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Assignment & Status')
                    ->schema([
                        Forms\Components\Placeholder::make('created_by')
                            ->label('Created By')
                            ->content(fn($record) => $record->creator?->name ?? 'N/A'),
                        Forms\Components\Placeholder::make('assigned_to')
                            ->label('Assigned To')
                            ->content(fn($record) => $record->assignedUser?->name ?? 'Unassigned'),
                        Forms\Components\Placeholder::make('assigned_operator')
                            ->label('Assigned Operator')
                            ->content(fn($record) => $record->assignedOperator?->name ?? 'Unassigned'),
                        Forms\Components\Placeholder::make('status')
                            ->label('Status')
                            ->content(fn($record) => LeadStatus::tryFrom($record->status)?->label() ?? $record->status ?? ''),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Travel Details')
                    ->schema([
                        Forms\Components\TextInput::make('subject')->label('Subject')->disabled(fn($context) => $context === 'view'),
                        Forms\Components\TextInput::make('country')->label('Country')->disabled(fn($context) => $context === 'view'),
                        Forms\Components\TextInput::make('destination')->label('Destination')->disabled(fn($context) => $context === 'view'),
                        Forms\Components\TextInput::make('number_of_adults')->label('Number of Adults')->disabled(fn($context) => $context === 'view'),
                        Forms\Components\TextInput::make('number_of_children')->label('Number of Children')->disabled(fn($context) => $context === 'view'),
                        Forms\Components\TextInput::make('number_of_infants')->label('Number of Infants')->disabled(fn($context) => $context === 'view'),
                        Forms\Components\TextInput::make('priority')->label('Priority')->disabled(fn($context) => $context === 'view'),
                        Forms\Components\DatePicker::make('arrival_date')->label('Arrival Date')->disabled(fn($context) => $context === 'view'),
                        Forms\Components\DatePicker::make('depature_date')->label('Departure Date')->disabled(fn($context) => $context === 'view'),
                        Forms\Components\TextInput::make('number_of_days')->label('Number of Days')->disabled(fn($context) => $context === 'view'),
                        Forms\Components\Textarea::make('tour_details')->label('Tour Details')->disabled(fn($context) => $context === 'view'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Status Management')
                    ->schema([
                        Forms\Components\Select::make('air_ticket_status')
                            ->label('Air Ticket Status')
                            ->options(ServiceStatus::options())
                            ->default('pending')
                            ->disabled(fn($context) => $context === 'view')
                            ->suffixIcon(fn ($state) => match ($state) {
                                'pending' => 'heroicon-o-clock',
                                'not_required' => 'heroicon-o-minus-circle',
                                'done' => 'heroicon-o-check-circle',
                                default => 'heroicon-o-question-mark-circle'
                            })
                            ->suffixIconColor(fn ($state) => 
                                ServiceStatus::tryFrom($state)?->color() ?? 'gray'
                            ),
                        Forms\Components\Select::make('hotel_status')
                            ->label('Hotel Status')
                            ->options(ServiceStatus::options())
                            ->default('pending')
                            ->disabled(fn($context) => $context === 'view')
                            ->suffixIcon(fn ($state) => match ($state) {
                                'pending' => 'heroicon-o-clock',
                                'not_required' => 'heroicon-o-minus-circle',
                                'done' => 'heroicon-o-check-circle',
                                default => 'heroicon-o-question-mark-circle'
                            })
                            ->suffixIconColor(fn ($state) => 
                                ServiceStatus::tryFrom($state)?->color() ?? 'gray'
                            ),
                        Forms\Components\Select::make('visa_status')
                            ->label('Visa Status')
                            ->options(ServiceStatus::options())
                            ->default('pending')
                            ->disabled()
                            ->suffixIcon(fn ($state) => match ($state) {
                                'pending' => 'heroicon-o-clock',
                                'not_required' => 'heroicon-o-minus-circle',
                                'done' => 'heroicon-o-check-circle',
                                default => 'heroicon-o-question-mark-circle'
                            })
                            ->suffixIconColor(fn ($state) => 
                                ServiceStatus::tryFrom($state)?->color() ?? 'gray'
                            )
                            ->helperText('Visa status can only be edited in Visa Leads tab'),
                        Forms\Components\Select::make('land_package_status')
                            ->label('Land Package Status')
                            ->options(ServiceStatus::options())
                            ->default('pending')
                            ->disabled(fn($context) => $context === 'view')
                            ->suffixIcon(fn ($state) => match ($state) {
                                'pending' => 'heroicon-o-clock',
                                'not_required' => 'heroicon-o-minus-circle',
                                'done' => 'heroicon-o-check-circle',
                                default => 'heroicon-o-question-mark-circle'
                            })
                            ->suffixIconColor(fn ($state) => 
                                ServiceStatus::tryFrom($state)?->color() ?? 'gray'
                            ),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Lead Costs')
                    ->schema([
                        Forms\Components\Repeater::make('leadCosts')
                            ->relationship('leadCosts')
                            ->schema([
                                Forms\Components\TextInput::make('invoice_number')
                                    ->label('Invoice Number')
                                    ->required(),
                                Forms\Components\TextInput::make('amount')
                                    ->label('Amount')
                                    ->numeric()
                                    ->step(0.01)
                                    ->prefix('$')
                                    ->required(),
                                Forms\Components\Textarea::make('details')
                                    ->label('Details')
                                    ->rows(2),
                                Forms\Components\TextInput::make('vendor_bill')
                                    ->label('Vendor Bill'),
                                Forms\Components\TextInput::make('vendor_amount')
                                    ->label('Vendor Amount')
                                    ->numeric()
                                    ->step(0.01)
                                    ->prefix('$'),
                                Forms\Components\Toggle::make('is_paid')
                                    ->label('Is Paid')
                                    ->default(false),
                            ])
                            ->createItemButtonLabel('Add Cost')
                            ->reorderable(false)
                            ->columns(2)
                            ->disabled(fn($context) => $context === 'view'),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Attachments')
                    ->schema([
                        Forms\Components\Repeater::make('attachments')
                            ->relationship('attachments')
                            ->schema([
                                Forms\Components\Select::make('type')
                                    ->label('Document Type')
                                    ->options([
                                        'lead_documents' => 'Lead Documents',
                                        'passport' => 'Passport',
                                        'other_documents' => 'Other Documents',
                                    ])
                                    ->required(),
                                self::getAttachmentFileUpload(),
                                Forms\Components\Hidden::make('original_name'),
                            ])
                            ->createItemButtonLabel('Add Attachment')
                            ->disableLabel()
                            ->columns(1)
                            ->disabled(fn($context) => $context === 'view'),
                    ])
                    ->collapsible(),

                Forms\Components\DateTimePicker::make('created_at')->label('Created At')->disabled(),
                Forms\Components\DateTimePicker::make('updated_at')->label('Updated At')->disabled(),
            ]);
    }
} 