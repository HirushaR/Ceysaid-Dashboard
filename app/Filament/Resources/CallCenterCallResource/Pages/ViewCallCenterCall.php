<?php

namespace App\Filament\Resources\CallCenterCallResource\Pages;

use App\Filament\Resources\CallCenterCallResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCallCenterCall extends ViewRecord
{
    protected static string $resource = CallCenterCallResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('make_call')
                ->label('Make Call')
                ->icon('heroicon-o-phone')
                ->color('success')
                ->action(function () {
                    // Update call attempt count and last call attempt when call button is clicked
                    $this->record->update([
                        'call_attempts' => ($this->record->call_attempts ?? 0) + 1,
                        'last_call_attempt' => now(),
                        'status' => \App\Models\CallCenterCall::STATUS_CALLED,
                    ]);
                    
                    $this->redirect(CallCenterCallResource::getUrl('edit', ['record' => $this->record]));
                })
                ->visible(fn () => auth()->user()?->isCallCenter()),
        ];
    }
}
