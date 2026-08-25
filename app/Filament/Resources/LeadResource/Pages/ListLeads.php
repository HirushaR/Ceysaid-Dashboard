<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use App\Support\FeatureFlag;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('workflow_pilot_status')
                ->label('Workflow pilot active')
                ->icon('heroicon-o-beaker')
                ->color('info')
                ->visible(fn (): bool => auth()->check() && app(FeatureFlag::class)->enabled('ui.lead_workspace', auth()->user()))
                ->modalHeading('Workflow pilot is active')
                ->modalDescription('The pilot currently adds workflow-engine actions inside eligible lead detail pages. Open a lead in New inquiry, Assigned, or Qualification to use the new actions. The redesigned list and application shell are not part of this pilot increment.')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()?->isMarketing() || auth()->user()?->isAdmin()),
        ];
    }
}
