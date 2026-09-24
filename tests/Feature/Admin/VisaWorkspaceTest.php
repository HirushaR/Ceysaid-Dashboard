<?php

namespace Tests\Feature\Admin;

use App\Enums\LeadStatus;
use App\Enums\ServiceStatus;
use App\Livewire\Admin\Visa\Index;
use App\Models\Lead;
use App\Models\PermissionGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VisaWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmed_leads_appear_for_operation_manager(): void
    {
        $manager = User::factory()->create(['role' => 'operation', 'is_manager' => true]);
        Lead::factory()->create(['status' => LeadStatus::CONFIRMED->value, 'customer_name' => 'Confirmed Visa Guest']);
        Lead::factory()->create(['status' => LeadStatus::SENT_TO_CUSTOMER->value, 'customer_name' => 'Not Confirmed Guest']);

        $this->actingAs($manager)->get(route('admin.dashboard.visa'))
            ->assertOk()
            ->assertSee('Visa Processing')
            ->assertSee('Confirmed Visa Guest')
            ->assertDontSee('Not Confirmed Guest');
    }

    public function test_dedicated_visa_member_only_sees_assigned_leads(): void
    {
        $member = $this->visaMember();
        Lead::factory()->create([
            'status' => LeadStatus::CONFIRMED->value,
            'visa_assigned_to' => $member->id,
            'customer_name' => 'My Visa Guest',
        ]);
        Lead::factory()->create([
            'status' => LeadStatus::CONFIRMED->value,
            'customer_name' => 'Unassigned Visa Guest',
        ]);

        $this->actingAs($member)->get(route('admin.dashboard.visa'))
            ->assertOk()
            ->assertSee('My Visa Guest')
            ->assertDontSee('Unassigned Visa Guest');
    }

    public function test_operation_member_without_visa_permission_cannot_open_queue(): void
    {
        $operator = User::factory()->create(['role' => 'operation', 'is_manager' => false]);

        $this->actingAs($operator)->get(route('admin.dashboard.visa'))->assertForbidden();
    }

    public function test_manager_assigns_and_member_processes_visa_lead(): void
    {
        $manager = User::factory()->create(['role' => 'operation', 'is_manager' => true]);
        $member = $this->visaMember();
        $lead = Lead::factory()->create([
            'status' => LeadStatus::CONFIRMED->value,
            'visa_status' => ServiceStatus::PENDING->value,
            'visa_assigned_to' => null,
        ]);

        $this->actingAs($manager);
        Livewire::test(Index::class)
            ->call('assignVisa', $lead->id, (string) $member->id)
            ->assertHasNoErrors();

        $this->assertSame($member->id, $lead->fresh()->visa_assigned_to);

        $this->actingAs($member);
        Livewire::test(Index::class)
            ->call('updateVisaStatus', $lead->id, ServiceStatus::IN_PROGRESS->value)
            ->assertHasNoErrors();

        $this->assertSame(ServiceStatus::IN_PROGRESS->value, $lead->fresh()->visa_status);
        $this->assertDatabaseHas('lead_action_logs', ['lead_id' => $lead->id, 'action' => 'visa_assigned']);
        $this->assertDatabaseHas('lead_action_logs', ['lead_id' => $lead->id, 'action' => 'visa_status_changed']);
    }

    private function visaMember(): User
    {
        $member = User::factory()->create(['role' => 'operation', 'is_manager' => false]);
        $group = PermissionGroup::where('name', 'visa_processing')->firstOrFail();
        $member->permissionGroups()->attach($group, [
            'granted_by' => $member->id,
            'granted_at' => now(),
        ]);

        return $member;
    }
}
