<?php

namespace Tests\Feature\Workflow;

use App\Models\DataMigrationIssue;
use App\Models\DataMigrationRun;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WorkflowAuditCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function audit_records_structured_issues_without_changing_leads(): void
    {
        $lead = Lead::factory()->create();
        DB::table('leads')->where('id', $lead->id)->update(['status' => 'unknown_state', 'reference_id' => null]);

        $this->artisan('workflow:audit-legacy', ['--dry-run' => true])->assertSuccessful();

        $this->assertDatabaseHas('data_migration_runs', ['migration_key' => 'audit_legacy', 'status' => 'completed']);
        $this->assertDatabaseHas('data_migration_issues', ['source_id' => $lead->id, 'issue_code' => 'missing_lead_reference']);
        $this->assertDatabaseHas('data_migration_issues', ['source_id' => $lead->id, 'issue_code' => 'unknown_lead_status']);
        $this->assertSame('unknown_state', $lead->fresh()->status);
    }

    #[Test]
    public function audit_can_fail_a_release_gate_on_critical_findings(): void
    {
        $lead = Lead::factory()->create();
        DB::table('leads')->where('id', $lead->id)->update(['reference_id' => null]);

        $this->artisan('workflow:audit-legacy', ['--fail-on' => 'critical'])->assertFailed();
    }

    #[Test]
    public function reconciliation_reports_canonical_divergence(): void
    {
        $lead = Lead::factory()->create();
        DB::table('leads')->where('id', $lead->id)->update(['status' => 'new', 'lifecycle_stage' => 'closed']);

        $this->artisan('workflow:reconcile')->assertSuccessful();

        $run = DataMigrationRun::where('migration_key', 'reconcile_workflow')->firstOrFail();
        $this->assertTrue(DataMigrationIssue::where('run_id', $run->id)->where('issue_code', 'lifecycle_mapping_mismatch')->exists());
    }
}
