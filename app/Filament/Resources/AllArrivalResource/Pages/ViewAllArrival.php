<?php

namespace App\Filament\Resources\AllArrivalResource\Pages;

use App\Filament\Resources\AllArrivalResource;
use App\Models\CallCenterCall;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAllArrival extends ViewRecord
{
    protected static string $resource = AllArrivalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('assign_to_me')
                ->label('Assign to Me')
                ->icon('heroicon-o-user-plus')
                ->color('success')
                ->action(function () {
                    CallCenterCall::create([
                        'lead_id' => $this->record->id,
                        'assigned_call_center_user' => auth()->id(),
                        'call_type' => CallCenterCall::CALL_TYPE_POST_ARRIVAL,
                        'status' => CallCenterCall::STATUS_ASSIGNED,
                    ]);
                    
                    $this->redirect(AllArrivalResource::getUrl('index'));
                })
                ->visible(fn () => auth()->user()?->isCallCenter()),
        ];
    }
}
