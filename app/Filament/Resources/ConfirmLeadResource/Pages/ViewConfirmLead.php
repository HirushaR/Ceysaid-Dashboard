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
                        ->disk('public')
                        ->directory('lead-attachments')
                        ->preserveFilenames()
                        ->downloadable()
                        ->openable()
                        ->required(),
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