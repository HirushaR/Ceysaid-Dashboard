<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\HasWorkflowMigrationOptions;
use App\Domain\LeadWorkflow\Services\LegacyLeadMapper;
use App\Domain\LeadWorkflow\Services\WorkflowEventWriter;
use App\Enums\LeadLifecycleStage;
use App\Models\Lead;
use App\Services\Migrations\MigrationRunManager;
use App\Support\MigrationExecutionContext;
use Illuminate\Console\Command;
use Ramsey\Uuid\Uuid;

class BackfillCanonicalLeads extends Command
{
    use HasWorkflowMigrationOptions;

    protected $signature = 'workflow:backfill-leads
        {--execute : Populate canonical fields; omit for a dry run}
        {--dry-run}
        {--chunk=500}
        {--after-id=}
        {--until-id=}
        {--run-id=}
        {--resume}
        {--limit=}
        {--fail-on=}
        {--output=}';

    protected $description = 'Backfill canonical lead workflow fields without changing legacy fields';

    public function handle(MigrationRunManager $runs, MigrationExecutionContext $context, LegacyLeadMapper $mapper, WorkflowEventWriter $events): int
    {
        $execute = (bool) $this->option('execute');
        $options = $this->migrationOptions();
        $run = $runs->start('backfill_canonical_leads', $execute ? 'execute' : 'dry-run', $options, $this->option('run-id'));
        $query = Lead::withTrashed()->orderBy('id')->where('id', '>', $this->option('resume') ? ($run->last_processed_id ?? 0) : ($options['after_id'] ?? 0));
        if ($options['until_id']) {
            $query->where('id', '<=', $options['until_id']);
        }

        $processed = 0;
        $context->run(function () use ($execute, $events, $mapper, $options, $query, $run, $runs, &$processed) {
            $query->chunkById($options['chunk'], function ($leads) use ($execute, $events, $mapper, $options, $run, $runs, &$processed) {
                foreach ($leads as $lead) {
                    if ($options['limit'] && $processed >= $options['limit']) {
                        return false;
                    }

                    $stage = $mapper->stage($lead);
                    $values = [
                        'lead_type' => $mapper->type($lead),
                        'lifecycle_stage' => $stage,
                        'sales_owner_id' => $lead->assigned_to,
                        'operations_owner_id' => $lead->assigned_operator,
                        'source_type' => $lead->platform,
                        'stage_entered_at' => $lead->updated_at ?? $lead->created_at ?? now(),
                        'last_internal_activity_at' => $lead->updated_at,
                    ];
                    if ($stage === LeadLifecycleStage::Confirmed && ! $lead->confirmed_at) {
                        $values['confirmed_at'] = $lead->updated_at ?? $lead->created_at;
                    }

                    if ($execute) {
                        $lead->forceFill($values)->saveQuietly();
                        $events->append($lead, 'migration.lead_backfilled', 'Canonical lead workflow fields backfilled', metadata: ['mapping_version' => 1, 'legacy_status' => $lead->status], source: 'migration', eventUuid: Uuid::uuid5(Uuid::NAMESPACE_URL, "ceysaid:lead:{$lead->id}:canonical-backfill:v1")->toString());
                        $run->increment('updated_count');
                    } else {
                        $run->increment('skipped_count');
                    }

                    $runs->checkpoint($run, $lead->id);
                    $processed++;
                }
            });
        });

        $summary = ['processed' => $processed, 'updated' => $execute ? $processed : 0, 'mode' => $execute ? 'execute' : 'dry-run'];
        $runs->complete($run, $summary);
        $this->line(json_encode(['run_uuid' => $run->run_uuid] + $summary, JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
