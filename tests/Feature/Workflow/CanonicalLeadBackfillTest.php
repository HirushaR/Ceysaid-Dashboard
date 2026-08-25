<?php

namespace Tests\Feature\Workflow;

use App\Enums\LeadLifecycleStage;
use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CanonicalLeadBackfillTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function reference_remediation_is_dry_run_unless_explicitly_executed(): void
    {
        $lead = Lead::factory()->create();
        DB::table('leads')->where('id', $lead->id)->update(['reference_id' => null]);

        $this->artisan('workflow:remediate-references')->assertSuccessful();
        $this->assertNull($lead->fresh()->reference_id);

        $this->artisan('workflow:remediate-references', ['--execute' => true])->assertSuccessful();
        $this->assertSame('MIG/'.str_pad((string) $lead->id, 8, '0', STR_PAD_LEFT), $lead->fresh()->reference_id);
    }

    #[Test]
    public function canonical_backfill_preserves_legacy_fields_and_is_idempotent(): void
    {
        $lead = Lead::factory()->create([
            'status' => LeadStatus::SENT_TO_CUSTOMER->value,
            'is_cruise_lead' => true,
            'is_group_lead' => false,
        ]);

        $this->artisan('workflow:backfill-leads', ['--execute' => true])->assertSuccessful();
        $lead->refresh();

        $this->assertSame(LeadStatus::SENT_TO_CUSTOMER->value, $lead->status);
        $this->assertSame(LeadLifecycleStage::QuoteSent, $lead->lifecycle_stage);
        $this->assertSame(LeadType::Cruise, $lead->lead_type);
        $this->assertSame($lead->assigned_to, $lead->sales_owner_id);
        $this->assertDatabaseCount('workflow_events', 1);

        $this->artisan('workflow:backfill-leads', ['--execute' => true])->assertSuccessful();
        $this->assertDatabaseCount('workflow_events', 1);
    }

    #[Test]
    public function canonical_backfill_is_non_mutating_in_dry_run_mode(): void
    {
        $lead = Lead::factory()->create(['lifecycle_stage' => null]);
        $stageBeforeDryRun = $lead->fresh()->lifecycle_stage;

        $this->artisan('workflow:backfill-leads')->assertSuccessful();

        $this->assertSame($stageBeforeDryRun, $lead->fresh()->lifecycle_stage);
        $this->assertDatabaseCount('workflow_events', 0);
    }
}
