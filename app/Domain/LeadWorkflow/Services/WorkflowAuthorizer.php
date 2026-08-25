<?php

namespace App\Domain\LeadWorkflow\Services;

use App\Domain\LeadWorkflow\Enums\LeadAction;
use App\Models\Lead;
use App\Models\User;

final class WorkflowAuthorizer
{
    public function allows(Lead $lead, LeadAction $action, User $actor): bool
    {
        if ($actor->isAdmin()) {
            return true;
        }

        if (in_array($action, [LeadAction::AssignSalesOwner, LeadAction::ClaimInquiry], true)) {
            return $actor->isMarketing() || $actor->isSales();
        }

        return $actor->isSales() && (int) $lead->sales_owner_id === (int) $actor->id;
    }
}
