<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\HasWorkflowMigrationOptions;
use App\Domain\LeadWorkflow\Services\LegacyWorkflowSynchronizer;
use App\Models\Lead;
use App\Models\WorkflowTask;
use App\Services\Migrations\MigrationRunManager;
use Illuminate\Console\Command;

class BackfillCurrentWorkflowTasks extends Command
{
    use HasWorkflowMigrationOptions;

    protected $signature = 'workflow:backfill-current-tasks
        {--execute : Create missing current tasks and next-action dates}
        {--dry-run}
        {--chunk=500}
        {--after-id=}
        {--until-id=}
        {--run-id=}
        {--resume}
        {--limit=}
        {--fail-on=}
        {--output=}';

    protected $description = 'Create one idempotent current Sales task for eligible canonical leads';

    public function handle(MigrationRunManager $runs, LegacyWorkflowSynchronizer $synchronizer): int
    {
        $execute = (bool) $this->option('execute');
        $options = $this->migrationOptions();
        $run = $runs->start('backfill_current_tasks', $execute ? 'execute' : 'dry-run', $options, $this->option('run-id'));
        $query = Lead::withTrashed()->whereNotNull('sales_owner_id')->whereIn('lifecycle_stage', ['assigned', 'qualification', 'ready_for_pricing', 'pricing', 'quote_sent', 'negotiation'])->orderBy('id');
        $query->where('id', '>', $this->option('resume') ? ($run->last_processed_id ?? 0) : ($options['after_id'] ?? 0));
        if ($options['until_id']) {
            $query->where('id', '<=', $options['until_id']);
        }

        $processed = 0;
        $created = 0;
        $query->chunkById($options['chunk'], function ($leads) use ($execute, $options, $run, $runs, $synchronizer, &$created, &$processed) {
            foreach ($leads as $lead) {
                if ($options['limit'] && $processed >= $options['limit']) {
                    return false;
                }
                $before = WorkflowTask::where('lead_id', $lead->id)->count();
                if ($execute) {
                    $synchronizer->syncCurrentTask($lead, true);
                    $after = WorkflowTask::where('lead_id', $lead->id)->count();
                    $created += max(0, $after - $before);
                }
                $runs->checkpoint($run, $lead->id);
                $processed++;
            }
        });

        $summary = ['processed' => $processed, 'created' => $created, 'mode' => $execute ? 'execute' : 'dry-run'];
        $runs->complete($run, $summary);
        $this->line(json_encode(['run_uuid' => $run->run_uuid] + $summary, JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
