<?php

namespace App\Filament\Resources\AllDepartureResource\Pages;

use App\Filament\Resources\AllDepartureResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAllDeparture extends EditRecord
{
    protected static string $resource = AllDepartureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
