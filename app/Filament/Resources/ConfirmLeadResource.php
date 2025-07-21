<?php

namespace App\Filament\Resources;

use App\Models\Lead;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ConfirmLeadResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use App\Enums\LeadStatus;

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
        return true; // Show to everyone
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereIn('status', [LeadStatus::CONFIRMED->value, LeadStatus::DOCUMENT_UPLOAD_COMPLETE->value]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConfirmLeads::route('/'),
            'view' => Pages\ViewConfirmLead::route('/{record}'),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
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
                Tables\Columns\BadgeColumn::make('status')
                    ->colors(LeadStatus::colorMap()),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
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
                Forms\Components\TextInput::make('created_by')->label('Created By')->disabled(fn($context) => $context === 'view'),
                Forms\Components\TextInput::make('assigned_to')->label('Assigned To')->disabled(fn($context) => $context === 'view'),
                Forms\Components\TextInput::make('assigned_operator')->label('Assigned Operator')->disabled(fn($context) => $context === 'view'),
                Forms\Components\Placeholder::make('status')
                    ->label('Status')
                    ->content(fn($record) => LeadStatus::tryFrom($record->status)?->label() ?? $record->status ?? '')
                    ->visible(fn($context) => $context === 'view'),
                Forms\Components\TextInput::make('contact_method')->label('Contact Method')->disabled(fn($context) => $context === 'view'),
                Forms\Components\TextInput::make('contact_value')->label('Contact Value')->disabled(fn($context) => $context === 'view'),
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
                        Forms\Components\FileUpload::make('file_path')
                            ->label('Attachment')
                            ->disk('public')
                            ->directory('lead-attachments')
                            ->preserveFilenames()
                            ->downloadable()
                            ->openable()
                            ->required()
                            ->saveUploadedFileUsing(function ($file, $record, $set) {
                                $path = $file->storeAs('lead-attachments', $file->getClientOriginalName(), 'public');
                                $set('file_path', $path);
                                $set('original_name', $file->getClientOriginalName());
                                return $path;
                            }),
                        Forms\Components\Hidden::make('original_name'),
                    ])
                    ->createItemButtonLabel('Add Attachment')
                    ->disableLabel()
                    ->columns(1)
                    ->disabled(fn($context) => $context === 'view'),
                Forms\Components\DateTimePicker::make('created_at')->label('Created At')->disabled(),
                Forms\Components\DateTimePicker::make('updated_at')->label('Updated At')->disabled(),
            ]);
    }
} 