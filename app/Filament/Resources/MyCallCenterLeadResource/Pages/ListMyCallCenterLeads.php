<?php

namespace App\Filament\Resources\MyCallCenterLeadResource\Pages;

use App\Filament\Resources\MyCallCenterLeadResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMyCallCenterLeads extends ListRecords
{
    protected static string $resource = MyCallCenterLeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Lead'),
        ];
    }
}
