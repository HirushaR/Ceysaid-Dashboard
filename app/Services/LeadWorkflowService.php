<?php

namespace App\Services;

use App\Enums\LeadStatus;
use App\Enums\OtherLeadStatus;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeadWorkflowService
{
    /** @param array<string, mixed> $attributes */
    public function create(array $attributes, User $creator, string $source = 'form'): Lead
    {
        return DB::transaction(function () use ($attributes, $creator, $source): Lead {
            $attributes['created_by'] = $creator->id;

            if (! empty($attributes['is_other_lead'])) {
                $attributes['status'] = LeadStatus::ASSIGNED_TO_SALES->value;
                $attributes['assigned_to'] = $creator->id;
                $attributes['assigned_operator'] = null;
                $attributes['other_lead_status'] = OtherLeadStatus::Draft->value;
            } elseif ($source === 'whatsapp' && ! $creator->isSales()) {
                $attributes['status'] = LeadStatus::NEW->value;
                $attributes['assigned_to'] = $creator->id;
                $attributes['assigned_operator'] = null;
            } elseif ($creator->isSales()) {
                $attributes['status'] = LeadStatus::ASSIGNED_TO_SALES->value;
                $attributes['assigned_to'] = $creator->id;
                $attributes['assigned_operator'] = null;
            } else {
                $attributes['status'] = LeadStatus::NEW->value;
                $attributes['assigned_to'] = null;
                $attributes['assigned_operator'] = null;
            }

            if (! empty($attributes['is_cruise_lead'])) {
                $attributes['is_group_lead'] = false;
            } elseif (! empty($attributes['is_group_lead'])) {
                $attributes['is_cruise_lead'] = false;
            }

            return Lead::create($attributes);
        });
    }

    public function transition(Lead $lead, LeadStatus $to, User $actor): Lead
    {
        if ($lead->is_other_lead) {
            throw ValidationException::withMessages(['status' => 'Other leads use their separate workflow.']);
        }

        $from = LeadStatus::tryFrom($lead->status);
        if (! $from || ! $this->canTransition($from, $to, $actor)) {
            throw ValidationException::withMessages([
                'status' => "You cannot move this lead from {$from?->label()} to {$to->label()}.",
            ]);
        }
        if ($to === LeadStatus::CONFIRMED && $lead->is_group_lead && ! $lead->tour_id) {
            throw ValidationException::withMessages(['tour_id' => 'A tour is required before confirming a group lead.']);
        }

        return DB::transaction(function () use ($lead, $to, $actor): Lead {
            $changes = ['status' => $to->value];
            if ($to === LeadStatus::ASSIGNED_TO_SALES) {
                $changes['assigned_to'] = $actor->id;
            }
            if ($to === LeadStatus::ASSIGNED_TO_OPERATIONS) {
                $changes['assigned_operator'] = $actor->id;
            }
            $lead->update($changes);

            return $lead->fresh();
        });
    }

    public function canTransition(LeadStatus $from, LeadStatus $to, User $actor): bool
    {
        if ($actor->isAdmin()) {
            return true;
        }

        return match ($from) {
            LeadStatus::NEW => $actor->isSales() && $to === LeadStatus::ASSIGNED_TO_SALES,
            LeadStatus::ASSIGNED_TO_SALES => $actor->isSales() && $to === LeadStatus::INFO_GATHER_COMPLETE,
            LeadStatus::INFO_GATHER_COMPLETE => $actor->isOperation() && $to === LeadStatus::ASSIGNED_TO_OPERATIONS,
            LeadStatus::ASSIGNED_TO_OPERATIONS, LeadStatus::RATE_REQUESTED, LeadStatus::AMENDMENT =>
                $actor->isOperation() && in_array($to, [LeadStatus::RATE_REQUESTED, LeadStatus::AMENDMENT, LeadStatus::OPERATION_COMPLETE], true),
            LeadStatus::OPERATION_COMPLETE => $actor->isSales() && $to === LeadStatus::SENT_TO_CUSTOMER,
            LeadStatus::SENT_TO_CUSTOMER => $actor->isSales() && $to === LeadStatus::CONFIRMED,
            LeadStatus::CONFIRMED => ($actor->isOperation() || $actor->isSales()) && $to === LeadStatus::DOCUMENT_UPLOAD_COMPLETE,
            default => false,
        };
    }
}
