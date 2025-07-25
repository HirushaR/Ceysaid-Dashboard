<?php

namespace App\Filament\Resources;

use App\Models\Lead;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\MyOperationLeadDashboardResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use App\Enums\LeadStatus;
use App\Enums\ServiceStatus;

class MyOperationLeadDashboardResource extends Resource
{
    protected static ?string $model = Lead::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'My Operation Lead';
    protected static ?string $label = 'My Operation Lead';
    protected static ?string $pluralLabel = 'My Operation Leads';
    protected static ?string $navigationGroup = 'Dashboard';

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && $user->isOperation();
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
        $user = auth()->user();
        return parent::getEloquentQuery()->where('assigned_operator', $user ? $user->id : null);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMyOperationLeadDashboards::route('/'),
            'view' => Pages\ViewMyOperationLeadDashboard::route('/{record}'),
            'edit' => Pages\EditMyOperationLeadDashboard::route('/{record}/edit'),
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
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(LeadStatus::options())
                    ->label('Lead Status'),
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
                Tables\Filters\SelectFilter::make('created_by')
                    ->relationship('creator', 'name')
                    ->label('Created By')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('operation_complete')
                    ->label('Operation Complete')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->status = \App\Enums\LeadStatus::OPERATION_COMPLETE->value;
                        $record->save();
                    })
                    ->visible(fn ($record) => $record->status !== \App\Enums\LeadStatus::ASSIGNED_TO_OPERATIONS->value),
            ])
            ->recordUrl(fn($record) => static::getUrl('view', ['record' => $record]));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Lead Information')
                    ->schema([
                        Forms\Components\TextInput::make('reference_id')->label('Reference ID'),
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

                Forms\Components\Section::make('Attachments')
                    ->schema([
                        Forms\Components\Repeater::make('attachments')
                            ->relationship('attachments')
                            ->schema([
                                Forms\Components\Select::make('type')
                                    ->label('Document Type')
                                    ->options([
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