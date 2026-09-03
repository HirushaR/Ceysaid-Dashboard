<?php

namespace Tests\Feature\Admin;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadQueueParityTest extends TestCase
{
    use RefreshDatabase;

    public function test_operation_workspace_only_contains_leads_assigned_to_operator(): void
    {
        $operator = User::factory()->create(['role' => 'operation']);
        $mine = Lead::factory()->create(['assigned_operator' => $operator->id, 'customer_name' => 'My operation guest']);
        Lead::factory()->create(['customer_name' => 'Another operation guest']);

        $this->actingAs($operator)->get(route('admin.dashboard.operations'))
            ->assertOk()
            ->assertSee($mine->customer_name)
            ->assertDontSee('Another operation guest');
    }

    public function test_call_centre_workspace_only_contains_leads_created_by_agent(): void
    {
        $agent = User::factory()->create(['role' => 'call_center']);
        Lead::factory()->create(['created_by' => $agent->id, 'customer_name' => 'My call centre guest']);
        Lead::factory()->create(['customer_name' => 'Another call centre guest']);

        $this->actingAs($agent)->get(route('admin.dashboard.call-centre'))
            ->assertOk()
            ->assertSee('My call centre guest')
            ->assertDontSee('Another call centre guest');
    }

    public function test_document_complete_and_archived_queues_are_isolated(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Lead::factory()->create([
            'status' => LeadStatus::DOCUMENT_UPLOAD_COMPLETE->value,
            'customer_name' => 'Documents finished',
        ]);
        Lead::factory()->create(['archived_at' => now(), 'customer_name' => 'Archived guest']);

        $this->actingAs($admin)->get(route('admin.dashboard.documents'))
            ->assertOk()
            ->assertSee('Documents finished')
            ->assertDontSee('Archived guest');
        $this->get(route('admin.dashboard.archived'))
            ->assertOk()
            ->assertSee('Archived guest')
            ->assertDontSee('Documents finished');
    }

    public function test_queue_access_is_role_restricted(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);

        $this->actingAs($sales)->get(route('admin.dashboard.operations'))->assertForbidden();
        $this->get(route('admin.dashboard.call-centre'))->assertForbidden();
        $this->get(route('admin.dashboard.archived'))->assertForbidden();
    }
}
