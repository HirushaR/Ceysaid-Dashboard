<?php

namespace Tests\Feature\OtherLead;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtherLeadIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_other_lead_visible_only_to_creator_sales_user(): void
    {
        $salesA = User::factory()->create(['role' => 'sales']);
        $salesB = User::factory()->create(['role' => 'sales']);

        $lead = Lead::factory()->otherLead()->create([
            'created_by' => $salesA->id,
            'assigned_to' => $salesA->id,
            'customer_name' => 'Test Customer',
        ]);

        $this->actingAs($salesA)->get(route('admin.dashboard.other'))
            ->assertOk()->assertSee('Test Customer');

        $this->actingAs($salesB)->get(route('admin.dashboard.other'))
            ->assertOk()->assertDontSee('Test Customer');
    }

    public function test_my_sales_and_other_lead_workspaces_remain_isolated(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $other = Lead::factory()->otherLead()->create([
            'created_by' => $sales->id,
            'assigned_to' => $sales->id,
        ]);
        $standard = Lead::factory()->create([
            'created_by' => $sales->id,
            'assigned_to' => $sales->id,
            'is_other_lead' => false,
        ]);

        $this->actingAs($sales)->get(route('admin.dashboard.my-sales'))
            ->assertOk()->assertSee($standard->customer_name)->assertDontSee($other->customer_name);
        $this->get(route('admin.dashboard.other'))
            ->assertOk()->assertSee($other->customer_name)->assertDontSee($standard->customer_name);
    }
}
