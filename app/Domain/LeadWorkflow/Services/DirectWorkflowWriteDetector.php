<?php

namespace App\Domain\LeadWorkflow\Services;

use App\Models\Lead;
use App\Support\MigrationExecutionContext;
use App\Support\WorkflowMutationContext;
use Illuminate\Support\Facades\Log;

final class DirectWorkflowWriteDetector
{
    private const PROTECTED_FIELDS = [
        'status', 'assigned_to', 'assigned_operator',
        'is_group_lead', 'is_cruise_lead', 'is_other_lead',
        'air_ticket_status', 'hotel_status', 'visa_status', 'land_package_status',
        'lifecycle_stage', 'sales_owner_id', 'operations_owner_id', 'lead_type',
    ];

    public function inspect(Lead $lead): void
    {
        if (! $lead->exists || app(MigrationExecutionContext::class)->active() || app(WorkflowMutationContext::class)->active()) {
            return;
        }

        $fields = array_values(array_intersect(array_keys($lead->getDirty()), self::PROTECTED_FIELDS));
        if ($fields === []) {
            return;
        }

        Log::warning('Direct workflow field write observed', [
            'lead_id' => $lead->getKey(),
            'fields' => $fields,
            'route' => request()?->route()?->getName(),
            'command' => app()->runningInConsole() ? ($_SERVER['argv'][1] ?? null) : null,
        ]);
    }
}
