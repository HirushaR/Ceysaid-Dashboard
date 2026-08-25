<?php

namespace Tests\Feature\Workflow;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WorkflowFoundationMigrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function phase_one_schema_is_additive_and_available(): void
    {
        $this->assertTrue(Schema::hasColumns('leads', ['status', 'assigned_to', 'lifecycle_stage', 'sales_owner_id', 'lock_version']));
        $this->assertTrue(Schema::hasTable('data_migration_runs'));
        $this->assertTrue(Schema::hasTable('data_migration_issues'));
        $this->assertTrue(Schema::hasTable('workflow_events'));
        $this->assertTrue(Schema::hasTable('workflow_requests'));
        $this->assertTrue(Schema::hasTable('workflow_outbox'));
        $this->assertTrue(Schema::hasTable('workflow_tasks'));
    }
}
