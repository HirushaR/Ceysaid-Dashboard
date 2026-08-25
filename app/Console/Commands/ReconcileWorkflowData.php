<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\HasWorkflowMigrationOptions;
use App\Domain\LeadWorkflow\Services\LegacyLeadMapper;
use App\Models\Lead;
use App\Services\Migrations\MigrationRunManager;
use Illuminate\Console\Command;

class ReconcileWorkflowData extends Command
{
    use HasWorkflowMigrationOptions;

    protected $signature = 'workflow:reconcile
        {--dry-run}
        {--chunk=500}
        {--after-id=}
        {--until-id=}
        {--run-id=}
        {--resume}
        {--limit=}
        {--fail-on=}
        {--output=}';

    protected $description = 'Compare canonical workflow fields with deterministic legacy mappings';

    public function handle(MigrationRunManager $runs, LegacyLeadMapper $mapper): int
    {
        $options = $this->migrationOptions();
        $run = $runs->start('reconcile_workflow', 'reconcile', $options, $this->option('run-id'));
        $query = Lead::withTrashed()->orderBy('id')->where('id', '>', $this->option('resume') ? ($run->last_processed_id ?? 0) : ($options['after_id'] ?? 0));
        if ($options['until_id']) {
            $query->where('id', '<=', $options['until_id']);
        }

        $processed = 0;
        $query->chunkById($options['chunk'], function ($leads) use ($mapper, $options, $run, $runs, &$processed) {
            foreach ($leads as $lead) {
                if ($options['limit'] && $processed >= $options['limit']) {
                    return false;
                }
                if ($lead->lifecycle_stage && $lead->lifecycle_stage !== $mapper->stage($lead)) {
                    $runs->issue($run, 'lifecycle_mapping_mismatch', 'error', 'lead', $lead->id, 'Canonical stage differs from the legacy mapping.', ['legacy' => $lead->status, 'canonical' => $lead->lifecycle_stage->value, 'expected' => $mapper->stage($lead)->value]);
                }
                if ($lead->lead_type && $lead->lead_type !== $mapper->type($lead)) {
                    $runs->issue($run, 'lead_type_mapping_mismatch', 'error', 'lead', $lead->id, 'Canonical lead type differs from legacy flags.');
                }
                if ($lead->sales_owner_id && $lead->sales_owner_id !== $lead->assigned_to) {
                    $runs->issue($run, 'sales_owner_mapping_mismatch', 'error', 'lead', $lead->id, 'Canonical and legacy Sales owners differ.');
                }
                if ($lead->operations_owner_id && $lead->operations_owner_id !== $lead->assigned_operator) {
                    $runs->issue($run, 'operations_owner_mapping_mismatch', 'error', 'lead', $lead->id, 'Canonical and legacy Operations owners differ.');
                }
                $runs->checkpoint($run, $lead->id);
                $processed++;
            }
        });

        $summary = ['processed' => (int) $run->fresh()->processed_count, 'mismatches' => $run->issues()->count()];
        $runs->complete($run, $summary);
        $this->line(json_encode(['run_uuid' => $run->run_uuid] + $summary, JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
