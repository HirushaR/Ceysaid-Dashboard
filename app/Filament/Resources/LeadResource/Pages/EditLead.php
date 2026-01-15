<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use App\Traits\SendsLeadNotifications;

class EditLead extends EditRecord
{
    use SendsLeadNotifications;

    protected static string $resource = LeadResource::class;

    protected array $originalData = [];

    protected function resolveRecord($key): \Illuminate\Database\Eloquent\Model
    {
        // Use the resource's query to ensure proper filtering
        return static::getResource()::getEloquentQuery()->findOrFail($key);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Store original values before save for comparison
        $this->originalData = $this->record->getOriginal();
        return $data;
    }

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

    protected function afterSave(): void
    {
        // Send notifications after lead is updated
        // Use stored original data from beforeSave
        if (!empty($this->originalData)) {
            $this->sendLeadUpdatedNotifications($this->record, $this->originalData);
        }
    }
}
