<?php

namespace App\Filament\Resources\OtherLeadResource\Pages;

use App\Enums\LeadStatus;
use App\Enums\OtherLeadStatus;
use App\Enums\Platform;
use App\Filament\Resources\OtherLeadResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOtherLead extends CreateRecord
{
    protected static string $resource = OtherLeadResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        return array_merge($data, [
            'is_other_lead' => true,
            'other_lead_status' => OtherLeadStatus::Draft->value,
            'created_by' => $user->id,
            'assigned_to' => $user->id,
            'status' => LeadStatus::ASSIGNED_TO_SALES->value,
            'platform' => Platform::OTHER->value,
            'is_group_lead' => false,
            'is_cruise_lead' => false,
            'customer_id' => $data['customer_id'] ?? null,
        ]);
    }

    protected function afterCreate(): void
    {
        $year = now()->year;
        $this->record->update([
            'reference_id' => "OL/{$year}/{$this->record->id}",
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
