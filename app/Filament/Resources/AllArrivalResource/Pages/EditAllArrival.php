<?php

namespace App\Filament\Resources\AllArrivalResource\Pages;

use App\Filament\Resources\AllArrivalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAllArrival extends EditRecord
{
    protected static string $resource = AllArrivalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
