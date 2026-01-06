<?php

namespace App\Filament\Resources\OfficeClosureResource\Pages;

use App\Filament\Resources\OfficeClosureResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOfficeClosure extends EditRecord
{
    protected static string $resource = OfficeClosureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
