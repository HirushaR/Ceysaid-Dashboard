<?php

namespace App\Filament\Resources\DocumentCompleteLeadResource\Pages;

use App\Filament\Resources\DocumentCompleteLeadResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditDocumentCompleteLead extends EditRecord
{
    protected static string $resource = DocumentCompleteLeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn() => auth()->user()?->isAdmin()),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Lead updated successfully')
            ->body('The visa status and other details have been updated.');
    }

    public function mutateFormDataBeforeSave(array $data): array
    {
        // Log the visa status change
        if (isset($data['visa_status']) && $data['visa_status'] !== $this->record->visa_status) {
            \Log::info('Visa status updated', [
                'lead_id' => $this->record->id,
                'reference_id' => $this->record->reference_id,
                'old_status' => $this->record->visa_status,
                'new_status' => $data['visa_status'],
                'updated_by' => auth()->user()?->id,
            ]);
        }

        return $data;
    }
} 