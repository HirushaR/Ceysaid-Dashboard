<?php

namespace Tests\Feature\Workflow;

use App\Enums\LeadLifecycleStage;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LegacyWorkflowSynchronizerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function legacy_lead_creation_populates_workspace_fields_and_first_task(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $lead = Lead::factory()->create([
            'status' => LeadStatus::ASSIGNED_TO_SALES->value,
            'assigned_to' => $sales->id,
            'lifecycle_stage' => null,
            'sales_owner_id' => null,
        ]);

        $lead->refresh();
        $this->assertSame(LeadLifecycleStage::Assigned, $lead->lifecycle_stage);
        $this->assertSame($sales->id, $lead->sales_owner_id);
        $this->assertNotNull($lead->next_action_at);
        $this->assertDatabaseHas('workflow_tasks', ['lead_id' => $lead->id, 'task_type' => 'first_contact', 'owner_id' => $sales->id, 'status' => 'open']);
    }

    #[Test]
    public function legacy_status_changes_update_canonical_stage_and_lock_version(): void
    {
        $lead = Lead::factory()->create(['status' => LeadStatus::ASSIGNED_TO_SALES->value]);
        $version = $lead->lock_version;

        $lead->update(['status' => LeadStatus::INFO_GATHER_COMPLETE->value]);

        $lead->refresh();
        $this->assertSame(LeadLifecycleStage::ReadyForPricing, $lead->lifecycle_stage);
        $this->assertGreaterThan($version, $lead->lock_version);
    }

    #[Test]
    public function current_task_backfill_is_explicit_and_idempotent(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $lead = Lead::withoutEvents(fn () => Lead::factory()->create(['assigned_to' => $sales->id, 'sales_owner_id' => $sales->id, 'lifecycle_stage' => LeadLifecycleStage::Assigned]));

        $this->artisan('workflow:backfill-current-tasks')->assertSuccessful();
        $this->assertDatabaseMissing('workflow_tasks', ['lead_id' => $lead->id]);

        $this->artisan('workflow:backfill-current-tasks', ['--execute' => true])->assertSuccessful();
        $this->artisan('workflow:backfill-current-tasks', ['--execute' => true])->assertSuccessful();
        $this->assertSame(1, $lead->workflowTasks()->count());
        $this->assertNotNull($lead->fresh()->next_action_at);
    }
}
