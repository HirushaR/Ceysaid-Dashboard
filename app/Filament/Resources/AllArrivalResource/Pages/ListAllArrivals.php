<?php

namespace App\Filament\Resources\AllArrivalResource\Pages;

use App\Filament\Resources\AllArrivalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAllArrivals extends ListRecords
{
    protected static string $resource = AllArrivalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action - this is a read-only resource for leads
        ];
    }
}
