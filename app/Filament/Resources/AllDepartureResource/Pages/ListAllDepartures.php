<?php

namespace App\Filament\Resources\AllDepartureResource\Pages;

use App\Filament\Resources\AllDepartureResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAllDepartures extends ListRecords
{
    protected static string $resource = AllDepartureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action - this is a read-only resource for leads
        ];
    }
}
