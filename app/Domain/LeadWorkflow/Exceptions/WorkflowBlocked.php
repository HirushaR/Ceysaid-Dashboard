<?php

namespace App\Domain\LeadWorkflow\Exceptions;

use RuntimeException;

class WorkflowBlocked extends RuntimeException
{
    public function __construct(public readonly array $blockers)
    {
        parent::__construct($blockers[0]['message'] ?? 'Workflow action is blocked.');
    }
}
