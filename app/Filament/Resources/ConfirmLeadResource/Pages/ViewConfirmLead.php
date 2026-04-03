<?php

namespace App\Filament\Resources\ConfirmLeadResource\Pages;

use App\Filament\Resources\ConfirmLeadResource;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\QuoteResource;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

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

            Action::make('create_quote')
                ->label('Create Quote')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->button()
                ->url(fn (): string => QuoteResource::getUrl('create').'?'.http_build_query(['lead_id' => $this->record->id]))
                ->visible(fn (): bool => QuoteResource::canCreate() && ! $this->record->quote),

            Action::make('create_invoice')
                ->label('Create Invoice')
                ->icon('heroicon-o-currency-rupee')
                ->color('success')
                ->button()
                ->url(fn (): string => InvoiceResource::getUrl('create').'?'.http_build_query(['lead_id' => $this->record->id]))
                ->visible(fn (): bool => InvoiceResource::canCreate()),

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
