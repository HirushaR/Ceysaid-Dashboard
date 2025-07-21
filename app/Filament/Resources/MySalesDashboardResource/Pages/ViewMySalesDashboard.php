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
                ->color('success')
                ->action(function () {
                    $this->record->status = \App\Enums\LeadStatus::INFO_GATHER_COMPLETE->value;
                    $this->record->save();
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Lead marked as Info Gather Complete.')
                        ->send();
                })
                ->visible(fn ($record) => $record->status !== \App\Enums\LeadStatus::OPERATION_COMPLETE->value),
            \Filament\Actions\Action::make('sent_to_customer')
                ->label('Sent to Customer')
                ->color('success')
                ->icon('heroicon-o-paper-airplane')
                ->requiresConfirmation()
                ->modalHeading('Are you sure?')
                ->modalDescription('Confirm that all steps are done and this lead will be marked as sent to customer.')
                ->action(function () {
                    $this->record->status = \App\Enums\LeadStatus::SENT_TO_CUSTOMER->value;
                    $this->record->save();
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Lead marked as Sent to Customer.')
                        ->send();
                })
                ->visible(fn ($record) => $record->status === \App\Enums\LeadStatus::OPERATION_COMPLETE->value),
        ];
    }
} 