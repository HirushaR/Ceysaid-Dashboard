<?php

namespace App\Filament\Resources\LeaveResource\Pages;

use App\Filament\Resources\LeaveResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Enums\LeaveStatus;
use Filament\Forms;

class ViewLeave extends ViewRecord
{
    protected static string $resource = LeaveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('approve')
                ->label('Approve Leave')
                ->icon('heroicon-m-check')
                ->color('success')
                ->visible(fn () => $this->record->isPending())
                ->requiresConfirmation()
                ->modalHeading('Approve Leave Request')
                ->modalDescription(fn () => "Are you sure you want to approve the {$this->record->type->getLabel()} request from {$this->record->user->name}?")
                ->modalSubmitActionLabel('Yes, Approve')
                ->action(function () {
                    $this->record->update([
                        'status' => LeaveStatus::APPROVED,
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                        'rejection_reason' => null,
                    ]);
                    
                    $this->redirect($this->getResource()::getUrl('index'));
                })
                ->successNotification(
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Leave Request Approved')
                        ->body(fn () => "Successfully approved {$this->record->user->name}'s {$this->record->type->getLabel()} request.")
                ),
            Actions\Action::make('reject')
                ->label('Reject Leave')
                ->icon('heroicon-m-x-mark')
                ->color('danger')
                ->visible(fn () => $this->record->isPending())
                ->modalHeading('Reject Leave Request')
                ->modalDescription(fn () => "You are about to reject the {$this->record->type->getLabel()} request from {$this->record->user->name}. Please provide a reason.")
                ->modalSubmitActionLabel('Reject Request')
                ->form([
                    Forms\Components\Textarea::make('rejection_reason')
                        ->label('Reason for Rejection')
                        ->required()
                        ->rows(3)
                        ->placeholder('Please provide a clear reason for rejecting this leave request...'),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => LeaveStatus::REJECTED,
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                        'rejection_reason' => $data['rejection_reason'],
                    ]);
                    
                    $this->redirect($this->getResource()::getUrl('index'));
                })
                ->successNotification(
                    \Filament\Notifications\Notification::make()
                        ->warning()
                        ->title('Leave Request Rejected')
                        ->body(fn () => "Rejected {$this->record->user->name}'s {$this->record->type->getLabel()} request.")
                ),
        ];
    }
}
