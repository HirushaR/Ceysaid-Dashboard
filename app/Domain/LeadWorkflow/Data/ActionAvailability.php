<?php

namespace App\Domain\LeadWorkflow\Data;

use App\Domain\LeadWorkflow\Enums\LeadAction;

final readonly class ActionAvailability
{
    public function __construct(public LeadAction $action, public bool $available, public array $blockers = []) {}
}
