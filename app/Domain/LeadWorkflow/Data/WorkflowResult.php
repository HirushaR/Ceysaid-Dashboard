<?php

namespace App\Domain\LeadWorkflow\Data;

use App\Domain\LeadWorkflow\Enums\LeadAction;
use App\Models\Lead;

final readonly class WorkflowResult
{
    public function __construct(
        public Lead $lead,
        public LeadAction $action,
        public string $correlationId,
        public array $eventUuids,
        public array $createdTaskIds,
        public array $completedTaskIds,
        public bool $wasIdempotentReplay = false,
    ) {}

    public function toArray(): array
    {
        return ['action' => $this->action->value, 'correlation_id' => $this->correlationId, 'event_uuids' => $this->eventUuids, 'created_task_ids' => $this->createdTaskIds, 'completed_task_ids' => $this->completedTaskIds, 'lock_version' => $this->lead->lock_version];
    }
}
