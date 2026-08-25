<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\HasWorkflowMigrationOptions;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Services\Migrations\MigrationRunManager;
use App\Support\MigrationExecutionContext;
use Illuminate\Console\Command;
use Throwable;

class AuditLegacyWorkflowData extends Command
{
    use HasWorkflowMigrationOptions;

    protected $signature = 'workflow:audit-legacy
        {--dry-run : Record findings without changing business data}
        {--chunk=500}
        {--after-id=}
        {--until-id=}
        {--run-id=}
        {--resume}
        {--limit=}
        {--fail-on= : Return a failure status when findings reach this severity}
        {--output=}';

    protected $description = 'Audit legacy workflow data and persist structured migration issues';

    public function handle(MigrationRunManager $runs, MigrationExecutionContext $context): int
    {
        $options = $this->migrationOptions();
        $run = $runs->start('audit_legacy', 'dry-run', $options, $this->option('run-id'));

        try {
            $context->run(fn () => $this->audit($run, $runs, $options));
            $summary = $this->summarize($run->fresh());
            $runs->complete($run, $summary);
            $this->line(json_encode(['run_uuid' => $run->run_uuid] + $summary, JSON_PRETTY_PRINT));

            return $this->shouldFail($summary) ? self::FAILURE : self::SUCCESS;
        } catch (Throwable $exception) {
            $runs->fail($run, $exception);
            throw $exception;
        }
    }

    private function audit($run, MigrationRunManager $runs, array $options): void
    {
        $validStatuses = array_column(LeadStatus::cases(), 'value');
        $processed = 0;
        $query = Lead::withTrashed()->orderBy('id');
        $afterId = $this->option('resume') ? ($run->last_processed_id ?? 0) : ($options['after_id'] ?? 0);
        $query->where('id', '>', $afterId);
        if ($options['until_id']) {
            $query->where('id', '<=', $options['until_id']);
        }

        $query->chunkById($options['chunk'], function ($leads) use (&$processed, $options, $run, $runs, $validStatuses) {
            foreach ($leads as $lead) {
                if ($options['limit'] && $processed >= $options['limit']) {
                    return false;
                }
                $this->auditLead($lead, $run, $runs, $validStatuses);
                $runs->checkpoint($run, $lead->id);
                $processed++;
            }
        });

        Lead::withTrashed()->selectRaw('reference_id, COUNT(*) as aggregate')
            ->whereNotNull('reference_id')->groupBy('reference_id')->havingRaw('COUNT(*) > 1')
            ->get()->each(fn ($row) => $runs->issue($run, 'duplicate_lead_reference', 'critical', 'lead', null, 'Lead reference is duplicated.', ['reference_id' => $row->reference_id, 'count' => $row->aggregate]));
    }

    private function auditLead(Lead $lead, $run, MigrationRunManager $runs, array $validStatuses): void
    {
        if (blank($lead->reference_id)) {
            $runs->issue($run, 'missing_lead_reference', 'critical', 'lead', $lead->id, 'Lead has no reference ID.');
        }
        if (! in_array($lead->status, $validStatuses, true)) {
            $runs->issue($run, 'unknown_lead_status', 'critical', 'lead', $lead->id, 'Lead has an unknown legacy status.', ['status' => $lead->status]);
        }
        if (collect([$lead->is_group_lead, $lead->is_cruise_lead, $lead->is_other_lead])->filter()->count() > 1) {
            $runs->issue($run, 'contradictory_lead_type_flags', 'error', 'lead', $lead->id, 'Multiple legacy lead-type flags are enabled.');
        }
        if ($lead->is_group_lead && $lead->status === LeadStatus::CONFIRMED->value && ! $lead->tour_id) {
            $runs->issue($run, 'confirmed_group_without_tour', 'critical', 'lead', $lead->id, 'Confirmed group lead has no tour.');
        }
        if ($lead->assigned_to && ! $lead->assignedUser) {
            $runs->issue($run, 'missing_sales_owner', 'error', 'lead', $lead->id, 'Assigned Sales user does not exist.', ['assigned_to' => $lead->assigned_to]);
        }
        if ($lead->assigned_operator && ! $lead->assignedOperator) {
            $runs->issue($run, 'missing_operations_owner', 'error', 'lead', $lead->id, 'Assigned Operations user does not exist.', ['assigned_operator' => $lead->assigned_operator]);
        }
    }

    private function summarize($run): array
    {
        return [
            'processed' => (int) $run->processed_count,
            'issues' => $run->issues()->count(),
            'critical' => $run->issues()->where('severity', 'critical')->count(),
            'errors' => $run->issues()->where('severity', 'error')->count(),
        ];
    }

    private function shouldFail(array $summary): bool
    {
        return $this->option('fail-on') === 'critical' && $summary['critical'] > 0;
    }
}
