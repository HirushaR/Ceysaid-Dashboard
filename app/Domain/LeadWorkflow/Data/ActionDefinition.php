<?php

namespace App\Domain\LeadWorkflow\Data;

use App\Domain\LeadWorkflow\Enums\LeadAction;
use App\Enums\LeadLifecycleStage;

final readonly class ActionDefinition
{
    /** @param list<LeadLifecycleStage> $allowedFromStages */
    public function __construct(
        public LeadAction $action,
        public array $allowedFromStages,
        public ?LeadLifecycleStage $targetStage,
        public string $eventType,
        public bool $requiresReason = false,
        public bool $requiresConfirmation = false,
    ) {}
}
