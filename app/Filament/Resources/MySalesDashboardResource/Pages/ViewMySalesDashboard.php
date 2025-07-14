<?php

namespace App\Filament\Resources\MySalesDashboardResource\Pages;

use App\Filament\Resources\MySalesDashboardResource;
use Filament\Resources\Pages\ViewRecord;

class ViewMySalesDashboard extends ViewRecord
{
    protected static string $resource = MySalesDashboardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\EditAction::make(),
            \Filament\Actions\Action::make('info_gather_complete')
                ->label('Info Gather Complete')
                ->action(function () {
                    $this->record->status = 'info_gather_complete';
                    $this->record->save();
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Lead marked as Info Gather Complete.')
                        ->send();
                }),
        ];
    }
} 