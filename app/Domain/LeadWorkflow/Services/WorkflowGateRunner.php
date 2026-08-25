<?php

namespace App\Domain\LeadWorkflow\Services;

use App\Domain\LeadWorkflow\Data\GateResult;
use App\Domain\LeadWorkflow\Enums\LeadAction;
use App\Models\Lead;
use App\Models\User;

final class WorkflowGateRunner
{
    public function __construct(private WorkflowActionRegistry $registry, private WorkflowAuthorizer $authorizer) {}

    public function check(Lead $lead, LeadAction $action, User $actor, array $payload = []): GateResult
    {
        $definition = $this->registry->get($action);
        $blockers = [];

        if (! $this->authorizer->allows($lead, $action, $actor)) {
            $blockers[] = ['code' => 'not_authorized', 'message' => 'You are not authorized to perform this action.'];
        }
        if (! in_array($lead->lifecycle_stage, $definition->allowedFromStages, true)) {
            $blockers[] = ['code' => 'invalid_stage', 'message' => 'This action is not available in the current lifecycle stage.'];
        }
        if (in_array($action, [LeadAction::AssignSalesOwner, LeadAction::ClaimInquiry], true) && empty($payload['sales_owner_id'])) {
            $blockers[] = ['code' => 'sales_owner_required', 'message' => 'Select a Sales owner.'];
        } elseif (in_array($action, [LeadAction::AssignSalesOwner, LeadAction::ClaimInquiry], true)) {
            $owner = User::find($payload['sales_owner_id']);
            if (! $owner || ! $owner->isSales()) {
                $blockers[] = ['code' => 'invalid_sales_owner', 'message' => 'The selected user is not an eligible Sales owner.'];
            } elseif ($actor->isSales() && ! $actor->isManager() && (int) $owner->id !== (int) $actor->id) {
                $blockers[] = ['code' => 'cannot_assign_other_sales_user', 'message' => 'Sales users may only claim an inquiry for themselves.'];
            }
        }
        if ($action === LeadAction::StartQualification && blank($lead->contact_value) && blank($lead->message)) {
            $blockers[] = ['code' => 'customer_contact_required', 'message' => 'Record customer contact or an active conversation first.'];
        }
        if ($action === LeadAction::CompleteQualification) {
            foreach (['destination', 'arrival_date', 'depature_date', 'number_of_adults'] as $field) {
                if (blank($lead->{$field})) {
                    $blockers[] = ['code' => "qualification_{$field}_required", 'message' => "Qualification requires {$field}."];
                }
            }
        }

        return new GateResult($blockers);
    }
}
