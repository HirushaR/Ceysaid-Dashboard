<?php

namespace Tests\Unit\Domain\LeadWorkflow;

use App\Domain\LeadWorkflow\Enums\LeadAction;
use App\Domain\LeadWorkflow\Services\WorkflowActionRegistry;
use App\Enums\LeadLifecycleStage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WorkflowActionRegistryTest extends TestCase
{
    #[Test]
    public function every_phase_one_action_has_one_definition(): void
    {
        $registry = app(WorkflowActionRegistry::class);

        $this->assertCount(count(LeadAction::cases()), $registry->all());
        foreach (LeadAction::cases() as $action) {
            $this->assertSame($action, $registry->get($action)->action);
        }
    }

    #[Test]
    public function confirmation_uses_the_approved_transition(): void
    {
        $definition = app(WorkflowActionRegistry::class)->get(LeadAction::ConfirmBooking);

        $this->assertSame(LeadLifecycleStage::Confirmed, $definition->targetStage);
        $this->assertContains(LeadLifecycleStage::QuoteSent, $definition->allowedFromStages);
        $this->assertTrue($definition->requiresConfirmation);
    }
}
