<?php

namespace App\Filament\Resources\OfficeClosureResource\Pages;

use App\Filament\Resources\OfficeClosureResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewOfficeClosure extends ViewRecord
{
    protected static string $resource = OfficeClosureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
