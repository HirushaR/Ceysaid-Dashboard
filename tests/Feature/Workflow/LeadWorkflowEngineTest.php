<?php

namespace Tests\Feature\Workflow;

use App\Domain\LeadWorkflow\Data\WorkflowRequestData;
use App\Domain\LeadWorkflow\Enums\LeadAction;
use App\Domain\LeadWorkflow\Exceptions\StaleWorkflowVersion;
use App\Domain\LeadWorkflow\Exceptions\WorkflowBlocked;
use App\Domain\LeadWorkflow\Services\AvailableLeadActions;
use App\Domain\LeadWorkflow\Services\LeadWorkflowEngine;
use App\Enums\LeadLifecycleStage;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LeadWorkflowEngineTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_executes_assignment_transactionally_and_replays_idempotently(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $lead = Lead::factory()->create(['lifecycle_stage' => LeadLifecycleStage::NewInquiry, 'sales_owner_id' => null, 'lock_version' => 1]);
        $request = new WorkflowRequestData('assign-'.$lead->id, 1, 'ui');

        $result = app(LeadWorkflowEngine::class)->execute($lead, LeadAction::ClaimInquiry, $sales, ['sales_owner_id' => $sales->id], $request);

        $this->assertSame(LeadLifecycleStage::Assigned, $result->lead->lifecycle_stage);
        $this->assertSame($sales->id, $result->lead->sales_owner_id);
        $this->assertSame(2, $result->lead->lock_version);
        $this->assertDatabaseHas('workflow_tasks', ['lead_id' => $lead->id, 'task_type' => 'first_contact', 'status' => 'open']);
        $this->assertDatabaseCount('workflow_events', 1);

        $replay = app(LeadWorkflowEngine::class)->execute($lead, LeadAction::ClaimInquiry, $sales, ['sales_owner_id' => $sales->id], $request);
        $this->assertTrue($replay->wasIdempotentReplay);
        $this->assertDatabaseCount('workflow_events', 1);
        $this->assertDatabaseCount('workflow_tasks', 1);
    }

    #[Test]
    public function it_completes_the_first_sales_vertical_slice(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $lead = Lead::factory()->create(['lifecycle_stage' => LeadLifecycleStage::NewInquiry, 'sales_owner_id' => null, 'lock_version' => 1]);
        $engine = app(LeadWorkflowEngine::class);

        $engine->execute($lead, LeadAction::ClaimInquiry, $sales, ['sales_owner_id' => $sales->id], new WorkflowRequestData('claim', 1));
        $engine->execute($lead->fresh(), LeadAction::StartQualification, $sales, [], new WorkflowRequestData('start', 2));
        $result = $engine->execute($lead->fresh(), LeadAction::CompleteQualification, $sales, [], new WorkflowRequestData('complete', 3));

        $this->assertSame(LeadLifecycleStage::ReadyForPricing, $result->lead->lifecycle_stage);
        $this->assertDatabaseHas('workflow_tasks', ['lead_id' => $lead->id, 'task_type' => 'prepare_pricing', 'status' => 'open']);
        $this->assertDatabaseCount('workflow_events', 3);
    }

    #[Test]
    public function it_rejects_stale_and_unauthorized_actions(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $otherSales = User::factory()->create(['role' => 'sales']);
        $lead = Lead::factory()->create(['lifecycle_stage' => LeadLifecycleStage::Assigned, 'sales_owner_id' => $sales->id, 'lock_version' => 2]);

        try {
            app(LeadWorkflowEngine::class)->execute($lead, LeadAction::StartQualification, $otherSales, [], new WorkflowRequestData('unauthorized', 2));
            $this->fail('Expected workflow blocker.');
        } catch (WorkflowBlocked $exception) {
            $this->assertSame('not_authorized', $exception->blockers[0]['code']);
        }

        $this->expectException(StaleWorkflowVersion::class);
        app(LeadWorkflowEngine::class)->execute($lead, LeadAction::StartQualification, $sales, [], new WorkflowRequestData('stale', 1));
    }

    #[Test]
    public function available_actions_include_stable_blockers(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $lead = Lead::factory()->create(['lifecycle_stage' => LeadLifecycleStage::Assigned, 'sales_owner_id' => $sales->id, 'contact_value' => null, 'message' => null]);

        $actions = collect(app(AvailableLeadActions::class)->for($lead, $sales))->keyBy(fn ($item) => $item->action->value);

        $this->assertFalse($actions[LeadAction::StartQualification->value]->available);
        $this->assertSame('customer_contact_required', $actions[LeadAction::StartQualification->value]->blockers[0]['code']);
    }

    #[Test]
    public function targeted_dual_write_mirrors_the_legacy_fields_in_the_same_action(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $lead = Lead::factory()->create(['status' => 'new', 'assigned_to' => null, 'lifecycle_stage' => LeadLifecycleStage::NewInquiry, 'sales_owner_id' => null, 'lock_version' => 1]);
        config()->set('workflow.flags.dual_write', ['enabled' => true, 'users' => [$sales->id]]);

        app(LeadWorkflowEngine::class)->execute($lead, LeadAction::ClaimInquiry, $sales, ['sales_owner_id' => $sales->id], new WorkflowRequestData('pilot-claim', 1));

        $lead->refresh();
        $this->assertSame('assigned_to_sales', $lead->status);
        $this->assertSame($sales->id, $lead->assigned_to);
        $this->assertSame(LeadLifecycleStage::Assigned, $lead->lifecycle_stage);
    }
}
