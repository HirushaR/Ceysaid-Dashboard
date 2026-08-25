<?php

namespace App\Domain\LeadWorkflow\Services;

use App\Enums\LeadLifecycleStage;
use App\Models\Lead;
use App\Models\User;
use App\Models\WorkflowTask;
use App\Support\MigrationExecutionContext;
use App\Support\WorkflowMutationContext;

final class LegacyWorkflowSynchronizer
{
    private const LEGACY_FIELDS = [
        'status', 'assigned_to', 'assigned_operator', 'platform',
        'is_group_lead', 'is_cruise_lead', 'is_other_lead',
    ];

    public function __construct(private LegacyLeadMapper $mapper) {}

    public function syncBeforeSave(Lead $lead): void
    {
        if ($this->shouldSkip() || ($lead->exists && ! $lead->isDirty(self::LEGACY_FIELDS))) {
            return;
        }

        $statusChanged = ! $lead->exists || $lead->isDirty('status');
        $lead->lead_type = $lead->exists ? $this->mapper->type($lead) : ($lead->lead_type ?? $this->mapper->type($lead));
        $lead->lifecycle_stage = $lead->exists ? $this->mapper->stage($lead) : ($lead->lifecycle_stage ?? $this->mapper->stage($lead));
        $lead->sales_owner_id = $lead->exists ? $lead->assigned_to : ($lead->sales_owner_id ?? $lead->assigned_to);
        $lead->operations_owner_id = $lead->exists ? $lead->assigned_operator : ($lead->operations_owner_id ?? $lead->assigned_operator);
        $lead->source_type = $lead->exists ? $lead->platform : ($lead->source_type ?? $lead->platform);
        $lead->stage_entered_at = $statusChanged ? now() : ($lead->stage_entered_at ?? now());
        $lead->last_internal_activity_at = now();

        if ($lead->exists) {
            $lead->lock_version = max(1, (int) $lead->lock_version) + 1;
        }
    }

    public function syncCurrentTask(Lead $lead, bool $force = false): void
    {
        if ((! $force && $this->shouldSkip()) || ! $lead->sales_owner_id) {
            return;
        }

        $task = match ($lead->lifecycle_stage) {
            LeadLifecycleStage::Assigned => ['first_contact', 'Make first customer contact', 4],
            LeadLifecycleStage::Qualification => ['complete_qualification', 'Complete lead qualification', 24],
            LeadLifecycleStage::ReadyForPricing, LeadLifecycleStage::Pricing => ['prepare_pricing', 'Prepare pricing', 24],
            LeadLifecycleStage::QuoteSent, LeadLifecycleStage::Negotiation => ['customer_follow_up', 'Follow up with customer', 24],
            default => null,
        };

        if (! $task) {
            return;
        }

        WorkflowTask::firstOrCreate(
            ['lead_id' => $lead->id, 'automation_key' => "lead:{$lead->id}:{$task[0]}"],
            ['task_type' => $task[0], 'title' => $task[1], 'owner_id' => $lead->sales_owner_id, 'owner_role' => 'sales', 'created_by' => $lead->created_by && User::whereKey($lead->created_by)->exists() ? $lead->created_by : null, 'due_at' => now()->addHours($task[2])],
        );
        $lead->newQuery()->whereKey($lead->id)->update(['next_action_at' => WorkflowTask::where('lead_id', $lead->id)->whereIn('status', ['open', 'in_progress'])->min('due_at')]);
        $lead->refresh();
    }

    private function shouldSkip(): bool
    {
        return app(MigrationExecutionContext::class)->active() || app(WorkflowMutationContext::class)->active();
    }
}
