<?php

namespace App\Domain\LeadWorkflow\Services;

use App\Domain\LeadWorkflow\Data\ActionAvailability;
use App\Domain\LeadWorkflow\Enums\LeadAction;
use App\Models\Lead;
use App\Models\User;

final class AvailableLeadActions
{
    private const SUPPORTED_ACTIONS = [
        LeadAction::AssignSalesOwner,
        LeadAction::ClaimInquiry,
        LeadAction::StartQualification,
        LeadAction::CompleteQualification,
    ];

    public function __construct(private WorkflowGateRunner $gates) {}

    /** @return list<ActionAvailability> */
    public function for(Lead $lead, User $actor): array
    {
        return array_map(function (LeadAction $action) use ($actor, $lead) {
            $payload = in_array($action, [LeadAction::AssignSalesOwner, LeadAction::ClaimInquiry], true) ? ['sales_owner_id' => $actor->id] : [];
            $result = $this->gates->check($lead, $action, $actor, $payload);

            return new ActionAvailability($action, $result->passed(), $result->blockers);
        }, self::SUPPORTED_ACTIONS);
    }
}
