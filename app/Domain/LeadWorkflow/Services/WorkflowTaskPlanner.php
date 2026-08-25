<?php

namespace App\Domain\LeadWorkflow\Services;

use App\Domain\LeadWorkflow\Enums\LeadAction;
use App\Enums\WorkflowTaskStatus;
use App\Models\Lead;
use App\Models\User;
use App\Models\WorkflowTask;

final class WorkflowTaskPlanner
{
    /** @return array{created:list<int>,completed:list<int>} */
    public function apply(Lead $lead, LeadAction $action, User $actor): array
    {
        $completed = [];
        $created = [];

        $completeTypes = match ($action) {
            LeadAction::StartQualification => ['first_contact'],
            LeadAction::CompleteQualification => ['complete_qualification'],
            default => [],
        };

        WorkflowTask::where('lead_id', $lead->id)->whereIn('task_type', $completeTypes)
            ->whereIn('status', [WorkflowTaskStatus::Open, WorkflowTaskStatus::InProgress])
            ->get()->each(function (WorkflowTask $task) use ($actor, &$completed) {
                $task->update(['status' => WorkflowTaskStatus::Completed, 'completed_at' => now(), 'completed_by' => $actor->id]);
                $completed[] = $task->id;
            });

        $next = match ($action) {
            LeadAction::AssignSalesOwner, LeadAction::ClaimInquiry => ['first_contact', 'Make first customer contact', 4],
            LeadAction::StartQualification => ['complete_qualification', 'Complete lead qualification', 24],
            LeadAction::CompleteQualification => ['prepare_pricing', 'Prepare pricing', 24],
            default => null,
        };

        if ($next) {
            $task = WorkflowTask::firstOrCreate(
                ['lead_id' => $lead->id, 'automation_key' => "lead:{$lead->id}:{$next[0]}"],
                ['task_type' => $next[0], 'title' => $next[1], 'owner_id' => $lead->sales_owner_id, 'owner_role' => 'sales', 'created_by' => $actor->id, 'due_at' => now()->addHours($next[2])],
            );
            if ($task->wasRecentlyCreated) {
                $created[] = $task->id;
            }
        }

        return ['created' => $created, 'completed' => $completed];
    }
}
