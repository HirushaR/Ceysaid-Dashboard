<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\HasWorkflowMigrationOptions;
use App\Models\Lead;
use App\Services\Migrations\MigrationRunManager;
use App\Support\MigrationExecutionContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RemediateLeadReferences extends Command
{
    use HasWorkflowMigrationOptions;

    protected $signature = 'workflow:remediate-references
        {--execute : Apply deterministic replacement references}
        {--dry-run}
        {--chunk=500}
        {--after-id=}
        {--until-id=}
        {--run-id=}
        {--resume}
        {--limit=}
        {--fail-on=}
        {--output=}';

    protected $description = 'Generate deterministic references for missing and duplicate legacy lead references';

    public function handle(MigrationRunManager $runs, MigrationExecutionContext $context): int
    {
        $execute = (bool) $this->option('execute');
        $run = $runs->start('remediate_lead_references', $execute ? 'execute' : 'dry-run', $this->migrationOptions(), $this->option('run-id'));
        $changes = 0;

        $context->run(function () use ($execute, $run, $runs, &$changes) {
            $duplicateIds = DB::table('leads')->select('reference_id')->whereNotNull('reference_id')
                ->groupBy('reference_id')->havingRaw('COUNT(*) > 1')->pluck('reference_id')
                ->flatMap(fn ($reference) => DB::table('leads')->where('reference_id', $reference)->orderBy('id')->pluck('id')->skip(1))
                ->all();

            Lead::withTrashed()->where(function ($query) use ($duplicateIds) {
                $query->whereNull('reference_id')->orWhere('reference_id', '')->when($duplicateIds !== [], fn ($query) => $query->orWhereIn('id', $duplicateIds));
            })->orderBy('id')->chunkById(max(1, (int) $this->option('chunk')), function ($leads) use ($execute, $run, $runs, &$changes) {
                foreach ($leads as $lead) {
                    $newReference = 'MIG/'.str_pad((string) $lead->id, 8, '0', STR_PAD_LEFT);
                    if ($execute) {
                        $lead->forceFill(['reference_id' => $newReference])->saveQuietly();
                        $run->increment('updated_count');
                    } else {
                        $run->increment('skipped_count');
                    }
                    $runs->checkpoint($run, $lead->id);
                    $changes++;
                }
            });
        });

        $summary = ['candidates' => $changes, 'updated' => $execute ? $changes : 0, 'mode' => $execute ? 'execute' : 'dry-run'];
        $runs->complete($run, $summary);
        $this->line(json_encode(['run_uuid' => $run->run_uuid] + $summary, JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
