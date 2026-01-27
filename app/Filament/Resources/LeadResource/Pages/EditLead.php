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
            Actions\Action::make('archive')
                ->label('Archive Lead')
                ->icon('heroicon-o-archive-box')
                ->color('warning')
                ->button()
                ->visible(fn () => auth()->user()?->isAdmin() && !$this->record->isArchived())
                ->requiresConfirmation()
                ->modalHeading('Archive Lead')
                ->modalDescription('Are you sure you want to archive this lead? It will be hidden from all dashboards but can be accessed from the Archive Leads dashboard.')
                ->action(function () {
                    $user = auth()->user();
                    $this->record->archived_at = now();
                    $this->record->archived_by = $user->id;
                    $this->record->save();
                    
                    // Log archive action
                    \App\Models\LeadActionLog::create([
                        'lead_id' => $this->record->id,
                        'user_id' => $user->id,
                        'action' => 'archived',
                        'description' => 'Lead archived',
                    ]);
                    
                    Notification::make()
                        ->success()
                        ->title('Lead archived successfully.')
                        ->send();
                    return redirect()->to(LeadResource::getUrl('index'));
                }),
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()?->isAdmin()),
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
