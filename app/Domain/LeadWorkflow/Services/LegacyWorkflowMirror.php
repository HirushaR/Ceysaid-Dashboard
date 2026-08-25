<?php

namespace App\Domain\LeadWorkflow\Services;

use App\Enums\LeadLifecycleStage;
use App\Enums\LeadStatus;
use App\Models\Lead;

final class LegacyWorkflowMirror
{
    public function mirror(Lead $lead): void
    {
        $lead->assigned_to = $lead->sales_owner_id;
        $lead->status = match ($lead->lifecycle_stage) {
            LeadLifecycleStage::NewInquiry => LeadStatus::NEW->value,
            LeadLifecycleStage::Assigned, LeadLifecycleStage::Qualification => LeadStatus::ASSIGNED_TO_SALES->value,
            LeadLifecycleStage::ReadyForPricing => LeadStatus::INFO_GATHER_COMPLETE->value,
            default => $lead->status,
        };
        $lead->save();
    }
}
