<?php

namespace App\Filament\Resources\ConfirmLeadResource\Pages;

use App\Filament\Resources\ConfirmLeadResource;
use App\Models\Invoice;
use App\Models\VendorBill;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ViewConfirmLead extends ViewRecord
{
    protected static string $resource = ConfirmLeadResource::class;



    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\EditAction::make()
                ->label('Edit')
                ->icon('heroicon-o-pencil')
                ->button(),

            Action::make('create_invoice')
                ->label('Create Invoice')
                ->icon('heroicon-o-currency-dollar')
                ->color('success')
                ->button()
                ->form([
                    Forms\Components\Section::make('Invoice Information')
                        ->schema([
                            Forms\Components\TextInput::make('invoice_number')
                                ->label('Invoice Number')
                                ->required()
                                ->unique('invoices', 'invoice_number')
                                ->maxLength(255)
                                ->placeholder('e.g., INV20252345'),
                            Forms\Components\TextInput::make('total_amount')
                                ->label('Total Amount')
                                ->required()
                                ->numeric()
                                ->step(0.01)
                                ->prefix('$'),
                            Forms\Components\Textarea::make('description')
                                ->label('Description')
                                ->rows(3)
                                ->placeholder('Brief description of the invoice')
                                ->columnSpanFull(),
                        ])
                        ->columns(2),

                    Forms\Components\Section::make('Payment Information')
                        ->schema([
                            Forms\Components\Select::make('status')
                                ->label('Payment Status')
                                ->options([
                                    'pending' => 'Pending',
                                    'partial' => 'Partially Paid',
                                    'paid' => 'Fully Paid',
                                ])
                                ->default('pending')
                                ->required()
                                ->live(),
                            Forms\Components\TextInput::make('payment_amount')
                                ->label('Payment Amount')
                                ->numeric()
                                ->step(0.01)
                                ->prefix('$')
                                ->visible(fn($get) => in_array($get('status'), ['partial', 'paid'])),
                            Forms\Components\DatePicker::make('payment_date')
                                ->label('Payment Date')
                                ->visible(fn($get) => in_array($get('status'), ['partial', 'paid'])),
                            Forms\Components\TextInput::make('receipt_number')
                                ->label('Receipt Number')
                                ->maxLength(255)
                                ->placeholder('e.g., RC202534')
                                ->visible(fn($get) => in_array($get('status'), ['partial', 'paid'])),
                        ])
                        ->columns(2),

                    Forms\Components\Section::make('Vendor Bills')
                        ->schema([
                            Forms\Components\Repeater::make('vendor_bills')
                                ->schema([
                                    Forms\Components\TextInput::make('vendor_name')
                                        ->label('Vendor Name')
                                        ->required()
                                        ->placeholder('e.g., IATA, TRAVEL BUDDY, MALAYSIA E VISA'),
                                    Forms\Components\TextInput::make('vendor_bill_number')
                                        ->label('Vendor Bill Number')
                                        ->required()
                                        ->placeholder('e.g., XO20252345'),
                                    Forms\Components\TextInput::make('bill_amount')
                                        ->label('Amount')
                                        ->required()
                                        ->numeric()
                                        ->step(0.01)
                                        ->prefix('$'),
                                    Forms\Components\Select::make('service_type')
                                        ->label('Service Type')
                                        ->options([
                                            'AIR TICKET' => 'Air Ticket',
                                            'HOTEL' => 'Hotel',
                                            'VISA' => 'Visa',
                                            'LAND PACKAGE' => 'Land Package',
                                            'INSURANCE' => 'Insurance',
                                            'OTHER' => 'Other',
                                        ])
                                        ->required()
                                        ->searchable(),
                                    Forms\Components\Textarea::make('service_details')
                                        ->label('Service Details')
                                        ->rows(2)
                                        ->placeholder('Additional details about the service')
                                        ->columnSpanFull(),
                                    Forms\Components\Select::make('payment_status')
                                        ->label('Payment Status')
                                        ->options([
                                            'pending' => 'Pending',
                                            'paid' => 'Paid',
                                        ])
                                        ->default('pending')
                                        ->required(),
                                ])
                                ->createItemButtonLabel('Add Vendor Bill')
                                ->reorderable(false)
                                ->columns(2)
                                ->collapsible()
                                ->cloneable()
                                ->defaultItems(0),
                        ])
                        ->description('Add vendor bills for expenses related to this invoice (optional)')
                        ->collapsible()
                        ->collapsed(),

                    Forms\Components\Section::make('Additional Notes')
                        ->schema([
                            Forms\Components\Textarea::make('notes')
                                ->label('Notes')
                                ->rows(3)
                                ->columnSpanFull(),
                        ])
                        ->collapsible(),
                ])
                ->action(function (array $data) {
                    // Extract vendor bills data
                    $vendorBillsData = $data['vendor_bills'] ?? [];
                    unset($data['vendor_bills']);
                    
                    // Create the invoice first
                    $invoice = $this->record->invoices()->create($data);
                    
                    // Create vendor bills if any were provided
                    if (!empty($vendorBillsData)) {
                        foreach ($vendorBillsData as $vendorBillData) {
                            $invoice->vendorBills()->create($vendorBillData);
                        }
                    }
                    
                    $vendorBillsCount = count($vendorBillsData);
                    $vendorBillsText = $vendorBillsCount > 0 ? " with {$vendorBillsCount} vendor bill(s)" : "";
                    
                    Notification::make()
                        ->success()
                        ->title('Invoice created successfully')
                        ->body("Invoice {$invoice->invoice_number} has been created for this lead{$vendorBillsText}.")
                        ->send();
                })
                ->modalHeading('Create Invoice')
                ->modalButton('Create Invoice')
                ->modalWidth('4xl'),

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