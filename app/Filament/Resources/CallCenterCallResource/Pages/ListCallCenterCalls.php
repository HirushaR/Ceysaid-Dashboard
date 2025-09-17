<?php

namespace App\Filament\Resources\CallCenterCallResource\Pages;

use App\Filament\Resources\CallCenterCallResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCallCenterCalls extends ListRecords
{
    protected static string $resource = CallCenterCallResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action - calls are created through assignment
        ];
    }
}
