<?php

namespace App\Filament\Resources\CallCenterCallResource\Pages;

use App\Filament\Resources\CallCenterCallResource;
use App\Models\CallCenterCall;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditCallCenterCall extends EditRecord
{
    protected static string $resource = CallCenterCallResource::class;

    protected function resolveRecord($key): Model
    {
        return static::getResource()::resolveRecordRouteBinding($key)
            ->load('lead');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Ensure lead data is available in the form
        if ($this->record && $this->record->lead) {
            $data['lead'] = [
                'customer_name' => $this->record->lead->customer_name,
                'destination' => $this->record->lead->destination,
                'arrival_date' => $this->record->lead->arrival_date,
                'depature_date' => $this->record->lead->depature_date,
                'number_of_adults' => $this->record->lead->number_of_adults,
                'number_of_children' => $this->record->lead->number_of_children,
                'contact_value' => $this->record->lead->contact_value,
                'tour_details' => $this->record->lead->tour_details,
            ];
        }
        
        return $data;
    }

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
            $currentStatus = $this->record->status;
            $newStatus = $data['status'];
            
            // Only increment if status is changing TO called or not_answered
            if (($newStatus === CallCenterCall::STATUS_CALLED || 
                 $newStatus === CallCenterCall::STATUS_NOT_ANSWERED) &&
                $currentStatus !== $newStatus) {
                $data['call_attempts'] = ($this->record->call_attempts ?? 0) + 1;
                $data['last_call_attempt'] = now();
            }
        }

        return $data;
    }
}