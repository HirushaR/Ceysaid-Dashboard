<?php

namespace Tests\Feature\OtherLead;

use App\Filament\Resources\OtherLeadResource;
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

        $this->actingAs($salesA);
        $this->assertTrue(OtherLeadResource::canView($lead));
        $this->assertSame(1, OtherLeadResource::getEloquentQuery()->whereKey($lead->id)->count());

        $this->actingAs($salesB);
        $this->assertFalse(OtherLeadResource::canView($lead));
        $this->assertSame(0, OtherLeadResource::getEloquentQuery()->whereKey($lead->id)->count());
    }

    public function test_my_sales_dashboard_includes_assigned_other_leads(): void
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

        $this->actingAs($sales);
        $ids = \App\Filament\Resources\MySalesDashboardResource::getEloquentQuery()->pluck('id')->all();
        $this->assertContains($standard->id, $ids);
        $this->assertContains($other->id, $ids);
    }
}
