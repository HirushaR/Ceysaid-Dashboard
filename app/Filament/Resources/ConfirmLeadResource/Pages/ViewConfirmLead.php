<?php

namespace App\Filament\Resources\ConfirmLeadResource\Pages;

use App\Filament\Resources\ConfirmLeadResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;

class ViewConfirmLead extends ViewRecord
{
    protected static string $resource = ConfirmLeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\EditAction::make(),
            Action::make('add_cost')
                ->label('Add Cost')
                ->icon('heroicon-o-currency-dollar')
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