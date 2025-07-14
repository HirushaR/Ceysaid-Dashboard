<?php

namespace App\Filament\Resources\MyOperationLeadDashboardResource\Pages;

use App\Filament\Resources\MyOperationLeadDashboardResource;
use Filament\Resources\Pages\ViewRecord;

class ViewMyOperationLeadDashboard extends ViewRecord
{
    protected static string $resource = MyOperationLeadDashboardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\EditAction::make(),
        ];
    }
} 