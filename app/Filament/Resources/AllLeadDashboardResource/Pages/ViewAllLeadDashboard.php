<?php

namespace App\Filament\Resources\AllLeadDashboardResource\Pages;

use App\Filament\Resources\AllLeadDashboardResource;
use Filament\Resources\Pages\ViewRecord;

class ViewAllLeadDashboard extends ViewRecord
{
    protected static string $resource = AllLeadDashboardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\EditAction::make(),
            \Filament\Actions\Action::make('assign_to_operation')
                ->label('Assign to Operation')
                ->action(function () {
                    $user = auth()->user();
                    $this->record->status = \App\Enums\LeadStatus::ASSIGNED_TO_OPERATIONS->value;
                    $this->record->assigned_operator = $user ? $user->id : null;
                    $this->record->save();
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Lead assigned to operation.')
                        ->send();
                    
                    // Redirect to All Lead Dashboard
                    return redirect()->to(AllLeadDashboardResource::getUrl('index'));
                }),
        ];
    }
} 