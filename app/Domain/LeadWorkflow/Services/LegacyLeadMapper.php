<?php

namespace App\Domain\LeadWorkflow\Services;

use App\Enums\LeadLifecycleStage;
use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Models\Lead;

final class LegacyLeadMapper
{
    public function type(Lead $lead): LeadType
    {
        return match (true) {
            (bool) $lead->is_group_lead => LeadType::Group,
            (bool) $lead->is_cruise_lead => LeadType::Cruise,
            (bool) $lead->is_other_lead => LeadType::Other,
            default => LeadType::Standard,
        };
    }

    public function stage(Lead $lead): LeadLifecycleStage
    {
        return match ($lead->status) {
            LeadStatus::NEW->value => LeadLifecycleStage::NewInquiry,
            LeadStatus::ASSIGNED_TO_SALES->value => LeadLifecycleStage::Assigned,
            LeadStatus::INFO_GATHER_COMPLETE->value => LeadLifecycleStage::ReadyForPricing,
            LeadStatus::RATE_REQUESTED->value, LeadStatus::PRICING_IN_PROGRESS->value => LeadLifecycleStage::Pricing,
            LeadStatus::SENT_TO_CUSTOMER->value => LeadLifecycleStage::QuoteSent,
            LeadStatus::AMENDMENT->value => LeadLifecycleStage::Negotiation,
            LeadStatus::CONFIRMED->value => LeadLifecycleStage::Confirmed,
            LeadStatus::ASSIGNED_TO_OPERATIONS->value => LeadLifecycleStage::InFulfilment,
            LeadStatus::DOCUMENT_UPLOAD_COMPLETE->value => LeadLifecycleStage::ReadyToTravel,
            LeadStatus::OPERATION_COMPLETE->value, LeadStatus::MARK_COMPLETED->value => LeadLifecycleStage::TravelCompleted,
            LeadStatus::MARK_CLOSED->value => LeadLifecycleStage::Closed,
            default => LeadLifecycleStage::NewInquiry,
        };
    }
}
