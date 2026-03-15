<?php

namespace App\Filament\Resources\MyCallCenterLeadResource\Pages;

use App\Enums\LeadStatus;
use App\Filament\Resources\MyCallCenterLeadResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMyCallCenterLead extends CreateRecord
{
    protected static string $resource = MyCallCenterLeadResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        $data['created_by'] = $user ? $user->id : null;
        $data['status'] = LeadStatus::NEW->value;
        // Do not set assigned_to - lead stays unassigned for sales
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return MyCallCenterLeadResource::getUrl('index');
    }
}
