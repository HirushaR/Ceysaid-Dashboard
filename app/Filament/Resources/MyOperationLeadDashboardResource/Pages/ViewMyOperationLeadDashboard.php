<?php

namespace App\Filament\Resources\MyOperationLeadDashboardResource\Pages;

use App\Filament\Resources\MyOperationLeadDashboardResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;

class ViewMyOperationLeadDashboard extends ViewRecord
{
    protected static string $resource = MyOperationLeadDashboardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\EditAction::make(),
            \Filament\Actions\Action::make('operation_complete')
                ->label('Operation Complete')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->action(function ($record) {
                    $record->status = \App\Enums\LeadStatus::OPERATION_COMPLETE->value;
                    $record->save();
                    Notification::make()
                        ->title('Operation marked as complete!')
                        ->success()
                        ->send();
                })
                ->visible(fn ($record) => $record->status !== \App\Enums\LeadStatus::OPERATION_COMPLETE->value),
        ];
    }
} 