<?php

namespace App\Filament\Resources\ConfirmLeadResource\Pages;

use App\Filament\Resources\ConfirmLeadResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ViewConfirmLead extends ViewRecord
{
    protected static string $resource = ConfirmLeadResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Header section with key info
                Components\Section::make('Lead Overview')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('reference_id')
                                    ->label('Reference ID')
                                    ->badge()
                                    ->color('gray'),
                                Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'new' => 'gray',
                                        'assigned_to_sales' => 'info',
                                        'assigned_to_operations' => 'warning',
                                        'info_gather_complete' => 'success',
                                        'pricing_in_progress' => 'primary',
                                        'sent_to_customer' => 'accent',
                                        'confirmed' => 'brand',
                                        'mark_closed' => 'danger',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn ($state) => \App\Enums\LeadStatus::tryFrom($state)?->label() ?? $state),
                                Components\TextEntry::make('priority')
                                    ->label('Priority')
                                    ->badge()
                                    ->color(fn (?string $state): string => match ($state) {
                                        'low' => 'gray',
                                        'medium' => 'warning',
                                        'high' => 'danger',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn ($state) => ucfirst($state ?? 'medium')),
                            ]),
                    ])
                    ->columns(1),

                // Customer Information
                Components\Section::make('Customer Information')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('customer_name')
                                    ->label('Customer Name')
                                    ->size(Components\TextEntry\TextEntrySize::Large)
                                    ->weight('bold'),
                                Components\TextEntry::make('platform')
                                    ->label('Source Platform')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'facebook' => 'info',
                                        'whatsapp' => 'success',
                                        'email' => 'warning',
                                        default => 'gray',
                                    }),
                            ]),
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('contact_method')
                                    ->label('Contact Method')
                                    ->formatStateUsing(fn ($state) => ucfirst($state ?? 'Not specified')),
                                Components\TextEntry::make('contact_value')
                                    ->label('Contact Value')
                                    ->placeholder('Not provided')
                                    ->copyable(),
                            ]),
                        Components\TextEntry::make('message')
                            ->label('Customer Message')
                            ->placeholder('No message provided')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                // Travel Details
                Components\Section::make('Travel Information')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('destination')
                                    ->label('Destination')
                                    ->placeholder('Not specified'),
                                Components\TextEntry::make('country')
                                    ->label('Country')
                                    ->placeholder('Not specified'),
                                Components\TextEntry::make('subject')
                                    ->label('Trip Subject')
                                    ->placeholder('Not specified'),
                            ]),
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('arrival_date')
                                    ->label('Arrival Date')
                                    ->date('M j, Y')
                                    ->placeholder('Not set'),
                                Components\TextEntry::make('depature_date')
                                    ->label('Departure Date')
                                    ->date('M j, Y')
                                    ->placeholder('Not set'),
                                Components\TextEntry::make('number_of_days')
                                    ->label('Duration')
                                    ->suffix(' days')
                                    ->placeholder('Not specified'),
                            ]),
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('number_of_adults')
                                    ->label('Adults')
                                    ->placeholder('0'),
                                Components\TextEntry::make('number_of_children')
                                    ->label('Children')
                                    ->placeholder('0'),
                                Components\TextEntry::make('number_of_infants')
                                    ->label('Infants')
                                    ->placeholder('0'),
                            ]),
                        Components\TextEntry::make('tour')
                            ->label('Tour Requirements')
                            ->placeholder('No requirements specified')
                            ->columnSpanFull(),
                        Components\TextEntry::make('tour_details')
                            ->label('Detailed Tour Information')
                            ->placeholder('No details provided')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                // Assignment Information
                Components\Section::make('Assignment & Team')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('assignedUser.name')
                                    ->label('Assigned Sales Rep')
                                    ->placeholder('Unassigned')
                                    ->badge()
                                    ->color('info'),
                                Components\TextEntry::make('assignedOperator.name')
                                    ->label('Assigned Operator')
                                    ->placeholder('Unassigned')
                                    ->badge()
                                    ->color('success'),
                            ]),
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('creator.name')
                                    ->label('Created By')
                                    ->placeholder('Unknown'),
                                Components\TextEntry::make('customer.name')
                                    ->label('Linked Customer')
                                    ->placeholder('No customer link'),
                            ]),
                    ])
                    ->columns(2)
                    ->collapsed(),

                // Service Status Information
                Components\Section::make('Service Status')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('air_ticket_status')
                                    ->label('Air Ticket')
                                    ->badge()
                                    ->color(fn (?string $state): string => match ($state) {
                                        'completed' => 'success',
                                        'in_progress' => 'warning',
                                        'pending' => 'gray',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state ?? 'pending'))),
                                Components\TextEntry::make('hotel_status')
                                    ->label('Hotel')
                                    ->badge()
                                    ->color(fn (?string $state): string => match ($state) {
                                        'completed' => 'success',
                                        'in_progress' => 'warning',
                                        'pending' => 'gray',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state ?? 'pending'))),
                            ]),
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('visa_status')
                                    ->label('Visa')
                                    ->badge()
                                    ->color(fn (?string $state): string => match ($state) {
                                        'completed' => 'success',
                                        'in_progress' => 'warning',
                                        'pending' => 'gray',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state ?? 'pending'))),
                                Components\TextEntry::make('land_package_status')
                                    ->label('Land Package')
                                    ->badge()
                                    ->color(fn (?string $state): string => match ($state) {
                                        'completed' => 'success',
                                        'in_progress' => 'warning',
                                        'pending' => 'gray',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state ?? 'pending'))),
                            ]),
                    ])
                    ->columns(2)
                    ->collapsed(),

                // System Information
                Components\Section::make('System Information')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('created_at')
                                    ->label('Created At')
                                    ->dateTime('M j, Y \a\t g:i A'),
                                Components\TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->dateTime('M j, Y \a\t g:i A'),
                            ]),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ])
            ->columns(1);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\EditAction::make()
                ->label('Edit')
                ->icon('heroicon-o-pencil')
                ->button(),
            Action::make('add_cost')
                ->label('Add Cost')
                ->icon('heroicon-o-currency-dollar')
                ->color('success')
                ->button()
                ->form([
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
                        ->rows(3),
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
                ->action(function (array $data) {
                    $this->record->leadCosts()->create($data);
                    Notification::make()
                        ->success()
                        ->title('Cost added successfully.')
                        ->send();
                })
                ->modalHeading('Add Lead Cost')
                ->modalButton('Add Cost'),
            Action::make('attach_documents')
                ->label('Attach Documents')
                ->icon('heroicon-o-paper-clip')
                ->color('primary')
                ->button()
                ->form([
                    Forms\Components\Select::make('type')
                        ->label('Document Type')
                        ->options([
                            'passport' => 'Passport',
                            'other_documents' => 'Other Documents',
                        ])
                        ->required(),
                    Forms\Components\FileUpload::make('file_path')
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
                            return $path;
                        }),
                ])
                ->action(function (array $data) {
                    $this->record->attachments()->create([
                        'type' => $data['type'],
                        'file_path' => $data['file_path'],
                        'original_name' => basename($data['file_path']),
                    ]);
                    Notification::make()
                        ->success()
                        ->title('Document attached successfully.')
                        ->send();
                })
                ->modalHeading('Attach Document')
                ->modalButton('Attach')
                ->visible(fn ($record) => $record->status !== \App\Enums\LeadStatus::DOCUMENT_UPLOAD_COMPLETE->value),
            Action::make('complete_upload_document')
                ->label('Complete Upload Document')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->button()
                ->requiresConfirmation()
                ->modalHeading('Are you sure?')
                ->modalDescription('Confirm that all required documents have been uploaded. This will mark the lead as Document Upload Complete.')
                ->action(function () {
                    $this->record->status = \App\Enums\LeadStatus::DOCUMENT_UPLOAD_COMPLETE->value;
                    $this->record->save();
                    Notification::make()
                        ->success()
                        ->title('Lead marked as Document Upload Complete.')
                        ->send();
                })
                ->visible(fn ($record) => $record->status !== \App\Enums\LeadStatus::DOCUMENT_UPLOAD_COMPLETE->value),
        ];
    }
} 