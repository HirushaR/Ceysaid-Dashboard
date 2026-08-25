<?php

namespace App\Domain\LeadWorkflow\Services;

use App\Domain\LeadWorkflow\Data\WorkflowRequestData;
use App\Domain\LeadWorkflow\Data\WorkflowResult;
use App\Domain\LeadWorkflow\Enums\LeadAction;
use App\Domain\LeadWorkflow\Exceptions\StaleWorkflowVersion;
use App\Domain\LeadWorkflow\Exceptions\WorkflowBlocked;
use App\Models\Lead;
use App\Models\User;
use App\Models\WorkflowRequest;
use App\Support\FeatureFlag;
use App\Support\WorkflowMutationContext;
use Illuminate\Support\Facades\DB;

final class LeadWorkflowEngine
{
    public function __construct(
        private WorkflowActionRegistry $registry,
        private WorkflowGateRunner $gates,
        private WorkflowTaskPlanner $tasks,
        private WorkflowEventWriter $events,
        private WorkflowMutationContext $context,
        private LegacyWorkflowMirror $legacyMirror,
        private FeatureFlag $flags,
    ) {}

    public function execute(Lead $lead, LeadAction $action, User $actor, array $payload, WorkflowRequestData $request): WorkflowResult
    {
        $existing = WorkflowRequest::where('lead_id', $lead->id)->where('idempotency_key', $request->idempotencyKey)->where('status', 'completed')->first();
        if ($existing) {
            return $this->replay($lead, $action, $existing);
        }

        return $this->context->run(fn () => DB::transaction(function () use ($action, $actor, $lead, $payload, $request) {
            $lockedLead = Lead::withTrashed()->lockForUpdate()->findOrFail($lead->id);
            if ((int) $lockedLead->lock_version !== $request->expectedLockVersion) {
                throw new StaleWorkflowVersion('The lead changed since it was loaded. Refresh and try again.');
            }

            $gateResult = $this->gates->check($lockedLead, $action, $actor, $payload);
            if (! $gateResult->passed()) {
                throw new WorkflowBlocked($gateResult->blockers);
            }

            $definition = $this->registry->get($action);
            $correlationId = $request->correlationId();
            $workflowRequest = WorkflowRequest::firstOrCreate(
                ['lead_id' => $lockedLead->id, 'idempotency_key' => $request->idempotencyKey],
                ['action' => $action->value, 'status' => 'processing', 'correlation_id' => $correlationId],
            );
            if ($workflowRequest->status === 'completed') {
                return $this->replay($lockedLead, $action, $workflowRequest);
            }

            $before = ['lifecycle_stage' => $lockedLead->lifecycle_stage?->value, 'sales_owner_id' => $lockedLead->sales_owner_id];
            if (in_array($action, [LeadAction::AssignSalesOwner, LeadAction::ClaimInquiry], true)) {
                $lockedLead->sales_owner_id = (int) $payload['sales_owner_id'];
            }
            $lockedLead->lifecycle_stage = $definition->targetStage;
            $lockedLead->stage_entered_at = now();
            $lockedLead->last_internal_activity_at = now();
            $lockedLead->lock_version++;
            $lockedLead->save();
            if ($this->flags->enabled('workflow.dual_write', $actor)) {
                $this->legacyMirror->mirror($lockedLead);
            }

            $taskResult = $this->tasks->apply($lockedLead, $action, $actor);
            $event = $this->events->append($lockedLead, $definition->eventType, $this->summary($action), $actor, $before, ['lifecycle_stage' => $lockedLead->lifecycle_stage?->value, 'sales_owner_id' => $lockedLead->sales_owner_id], source: $request->source, correlationId: $correlationId);
            $result = new WorkflowResult($lockedLead, $action, $correlationId, [$event->event_uuid], $taskResult['created'], $taskResult['completed']);
            $workflowRequest->update(['status' => 'completed', 'result' => $result->toArray()]);

            return $result;
        }));
    }

    private function replay(Lead $lead, LeadAction $action, WorkflowRequest $request): WorkflowResult
    {
        $result = $request->result;

        return new WorkflowResult($lead->fresh(), $action, $request->correlation_id, $result['event_uuids'] ?? [], $result['created_task_ids'] ?? [], $result['completed_task_ids'] ?? [], true);
    }

    private function summary(LeadAction $action): string
    {
        return match ($action) {
            LeadAction::AssignSalesOwner, LeadAction::ClaimInquiry => 'Sales owner assigned',
            LeadAction::StartQualification => 'Qualification started',
            LeadAction::CompleteQualification => 'Qualification completed',
            default => 'Workflow action completed',
        };
    }
}
