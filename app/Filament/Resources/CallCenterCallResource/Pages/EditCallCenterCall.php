<?php

namespace App\Filament\Resources\CallCenterCallResource\Pages;

use App\Filament\Resources\CallCenterCallResource;
use App\Models\CallCenterCall;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCallCenterCall extends EditRecord
{
    protected static string $resource = CallCenterCallResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Update call attempt count and last call attempt when call status changes
        if (isset($data['status'])) {
            if ($data['status'] === CallCenterCall::STATUS_CALLED || 
                $data['status'] === CallCenterCall::STATUS_NOT_ANSWERED) {
                $data['call_attempts'] = ($this->record->call_attempts ?? 0) + 1;
                $data['last_call_attempt'] = now();
            }
        }

        return $data;
    }
}