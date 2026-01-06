<?php

namespace App\Filament\Resources\OfficeClosureResource\Pages;

use App\Filament\Resources\OfficeClosureResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOfficeClosures extends ListRecords
{
    protected static string $resource = OfficeClosureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
