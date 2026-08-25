<?php

namespace Tests\Feature\Workflow;

use App\Domain\LeadWorkflow\Services\WorkflowEventWriter;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WorkflowEventWriterTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_appends_a_correlated_immutable_event(): void
    {
        $lead = Lead::factory()->create();
        $event = app(WorkflowEventWriter::class)->append(
            $lead,
            'migration.lead_audited',
            'Lead audited',
            source: 'migration',
            correlationId: '8ef01936-c984-4e16-a2cd-66a008cf07ae',
        );

        $this->assertSame($lead->id, $event->lead_id);
        $this->assertSame('migration', $event->source);

        $this->expectException(LogicException::class);
        $event->update(['summary' => 'Changed']);
    }
}
