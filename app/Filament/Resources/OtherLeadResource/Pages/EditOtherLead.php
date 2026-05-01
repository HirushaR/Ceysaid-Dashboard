<?php

namespace App\Filament\Resources\OtherLeadResource\Pages;

use App\Filament\Resources\OtherLeadResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOtherLead extends EditRecord
{
    protected static string $resource = OtherLeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
