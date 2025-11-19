<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditLead extends EditRecord
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('assign_to_me')
                ->label('Assign to Me')
                ->visible(fn() => auth()->user()?->isSales())
                ->action(function () {
                    $user = auth()->user();
                    $this->record->assigned_to = $user->id;
                    $this->record->status = \App\Enums\LeadStatus::ASSIGNED_TO_SALES->value;
                    $this->record->save();
                    Notification::make()
                        ->success()
                        ->title('Lead assigned to you and status updated.')
                        ->send();
                }),
        ];
    }
}
