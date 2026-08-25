<?php

namespace App\Domain\LeadWorkflow\Services;

use App\Models\Lead;
use App\Models\User;
use App\Models\WorkflowEvent;
use Illuminate\Support\Str;

final class WorkflowEventWriter
{
    public function append(
        Lead $lead,
        string $eventType,
        string $summary,
        ?User $actor = null,
        array $before = [],
        array $after = [],
        array $metadata = [],
        string $source = 'ui',
        ?string $correlationId = null,
        ?string $eventUuid = null,
    ): WorkflowEvent {
        $attributes = [
            'event_uuid' => $eventUuid ?? (string) Str::uuid(),
            'lead_id' => $lead->getKey(),
            'event_type' => $eventType,
            'event_version' => 1,
            'actor_type' => $actor ? 'user' : 'system',
            'actor_id' => $actor?->getKey(),
            'occurred_at' => now(),
            'recorded_at' => now(),
            'correlation_id' => $correlationId,
            'source' => $source,
            'summary' => $summary,
            'before' => $before ?: null,
            'after' => $after ?: null,
            'metadata' => $metadata ?: null,
            'request_id' => request()?->header('X-Request-ID'),
        ];

        return $eventUuid
            ? WorkflowEvent::firstOrCreate(['event_uuid' => $eventUuid], $attributes)
            : WorkflowEvent::create($attributes);
    }
}
