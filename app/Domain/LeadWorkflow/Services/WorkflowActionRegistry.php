<?php

namespace App\Domain\LeadWorkflow\Services;

use App\Domain\LeadWorkflow\Data\ActionDefinition;
use App\Domain\LeadWorkflow\Enums\LeadAction;
use App\Enums\LeadLifecycleStage as Stage;
use InvalidArgumentException;

final class WorkflowActionRegistry
{
    /** @return array<string, ActionDefinition> */
    public function all(): array
    {
        return collect($this->definitions())->keyBy(fn (ActionDefinition $item) => $item->action->value)->all();
    }

    public function get(LeadAction $action): ActionDefinition
    {
        return $this->all()[$action->value]
            ?? throw new InvalidArgumentException("Workflow action [{$action->value}] is not registered.");
    }

    /** @return list<ActionDefinition> */
    private function definitions(): array
    {
        return [
            new ActionDefinition(LeadAction::AssignSalesOwner, [Stage::NewInquiry], Stage::Assigned, 'assignment.sales_assigned'),
            new ActionDefinition(LeadAction::ClaimInquiry, [Stage::NewInquiry], Stage::Assigned, 'assignment.sales_assigned'),
            new ActionDefinition(LeadAction::StartQualification, [Stage::Assigned], Stage::Qualification, 'lifecycle.transitioned'),
            new ActionDefinition(LeadAction::CompleteQualification, [Stage::Qualification], Stage::ReadyForPricing, 'lifecycle.transitioned'),
            new ActionDefinition(LeadAction::ReturnToQualification, [Stage::ReadyForPricing, Stage::Pricing], Stage::Qualification, 'lifecycle.transitioned', true),
            new ActionDefinition(LeadAction::StartPricing, [Stage::ReadyForPricing, Stage::Negotiation], Stage::Pricing, 'lifecycle.transitioned'),
            new ActionDefinition(LeadAction::SendQuote, [Stage::Pricing], Stage::QuoteSent, 'quote.sent', false, true),
            new ActionDefinition(LeadAction::StartAmendment, [Stage::QuoteSent], Stage::Negotiation, 'lifecycle.transitioned'),
            new ActionDefinition(LeadAction::ConfirmBooking, [Stage::QuoteSent, Stage::Negotiation], Stage::Confirmed, 'lead.confirmed', false, true),
            new ActionDefinition(LeadAction::SubmitHandoff, [Stage::Confirmed], Stage::OperationsHandover, 'handoff.submitted', false, true),
            new ActionDefinition(LeadAction::AcceptHandoff, [Stage::OperationsHandover], Stage::InFulfilment, 'handoff.accepted', false, true),
            new ActionDefinition(LeadAction::MarkReadyToTravel, [Stage::InFulfilment], Stage::ReadyToTravel, 'readiness.reviewed', false, true),
            new ActionDefinition(LeadAction::RevokeReadiness, [Stage::ReadyToTravel], Stage::InFulfilment, 'readiness.revoked', true),
            new ActionDefinition(LeadAction::MarkTravelCompleted, [Stage::ReadyToTravel], Stage::TravelCompleted, 'lifecycle.transitioned'),
            new ActionDefinition(LeadAction::CloseLead, array_values(array_filter(Stage::cases(), fn (Stage $stage) => $stage !== Stage::Closed)), Stage::Closed, 'lead.closed', true, true),
            new ActionDefinition(LeadAction::ReopenLead, [Stage::Closed], null, 'lead.reopened', true, true),
        ];
    }
}
