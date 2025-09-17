<?php

namespace App\Filament\Resources\AllDepartureResource\Pages;

use App\Filament\Resources\AllDepartureResource;
use App\Models\CallCenterCall;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAllDeparture extends ViewRecord
{
    protected static string $resource = AllDepartureResource::class;

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
                        'call_type' => CallCenterCall::CALL_TYPE_PRE_DEPARTURE,
                        'status' => CallCenterCall::STATUS_ASSIGNED,
                    ]);
                    
                    $this->redirect(AllDepartureResource::getUrl('index'));
                })
                ->visible(fn () => auth()->user()?->isCallCenter()),
        ];
    }
}
