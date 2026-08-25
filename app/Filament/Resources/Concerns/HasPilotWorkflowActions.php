<?php

namespace App\Filament\Resources\Concerns;

use App\Domain\LeadWorkflow\Data\ActionAvailability;
use App\Domain\LeadWorkflow\Data\WorkflowRequestData;
use App\Domain\LeadWorkflow\Enums\LeadAction;
use App\Domain\LeadWorkflow\Exceptions\StaleWorkflowVersion;
use App\Domain\LeadWorkflow\Exceptions\WorkflowBlocked;
use App\Domain\LeadWorkflow\Services\AvailableLeadActions;
use App\Domain\LeadWorkflow\Services\LeadWorkflowEngine;
use App\Support\FeatureFlag;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

trait HasPilotWorkflowActions
{
    protected function pilotWorkflowEnabled(): bool
    {
        return auth()->check() && app(FeatureFlag::class)->enabled('ui.lead_workspace', auth()->user());
    }

    /** @return list<Action> */
    protected function pilotWorkflowActions(): array
    {
        if (! $this->pilotWorkflowEnabled() || $this->record->is_other_lead) {
            return [];
        }

        $availability = collect(app(AvailableLeadActions::class)->for($this->record, auth()->user()))
            ->keyBy(fn (ActionAvailability $item) => $item->action->value);

        return [
            $this->makePilotAction(LeadAction::ClaimInquiry, 'Claim inquiry', 'heroicon-o-user-plus', 'success', $availability),
            $this->makePilotAction(LeadAction::StartQualification, 'Start qualification', 'heroicon-o-play', 'info', $availability),
            $this->makePilotAction(LeadAction::CompleteQualification, 'Complete qualification', 'heroicon-o-check-circle', 'success', $availability),
        ];
    }

    private function makePilotAction(LeadAction $workflowAction, string $label, string $icon, string $color, $availability): Action
    {
        /** @var ActionAvailability $state */
        $state = $availability[$workflowAction->value];
        $blocker = $state->blockers[0]['message'] ?? null;

        return Action::make('pilot_'.$workflowAction->value)
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->button()
            ->visible(fn (): bool => $state->available || collect($state->blockers)->doesntContain('code', 'invalid_stage'))
            ->disabled(fn (): bool => ! $state->available)
            ->tooltip($blocker)
            ->requiresConfirmation()
            ->modalHeading($label.'?')
            ->modalDescription('This action is processed by the new workflow engine and recorded in the workflow timeline.')
            ->action(fn () => $this->executePilotWorkflowAction($workflowAction));
    }

    private function executePilotWorkflowAction(LeadAction $action): void
    {
        try {
            $payload = $action === LeadAction::ClaimInquiry ? ['sales_owner_id' => auth()->id()] : [];
            app(LeadWorkflowEngine::class)->execute(
                $this->record,
                $action,
                auth()->user(),
                $payload,
                new WorkflowRequestData((string) Str::uuid(), (int) $this->record->lock_version, 'ui'),
            );
            $this->record->refresh();
            Notification::make()->success()->title('Workflow action completed')->body('The lead and its next task were updated.')->send();
        } catch (WorkflowBlocked $exception) {
            Notification::make()->danger()->title('Action blocked')->body($exception->getMessage())->send();
        } catch (StaleWorkflowVersion $exception) {
            $this->record->refresh();
            Notification::make()->warning()->title('Lead changed')->body($exception->getMessage())->send();
        }
    }
}
