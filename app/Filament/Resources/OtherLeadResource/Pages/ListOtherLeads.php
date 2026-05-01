<?php

namespace App\Filament\Resources\OtherLeadResource\Pages;

use App\Filament\Resources\OtherLeadResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOtherLeads extends ListRecords
{
    protected static string $resource = OtherLeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
