<?php

namespace Tests\Feature\Workflow;

use App\Enums\LeadLifecycleStage;
use App\Filament\Pages\LeadWorkspace;
use App\Filament\Pages\MyWork;
use App\Filament\Pages\SalesPipeline;
use App\Filament\Pages\SalesWorkspace;
use App\Models\Lead;
use App\Models\User;
use App\Support\FeatureFlag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SalesWorkspaceUiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function pilot_sales_user_can_render_all_workspace_surfaces(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $lead = Lead::factory()->create(['sales_owner_id' => $sales->id, 'lifecycle_stage' => LeadLifecycleStage::Assigned]);
        $completed = Lead::factory()->create(['sales_owner_id' => $sales->id, 'lifecycle_stage' => LeadLifecycleStage::TravelCompleted]);
        config()->set('workflow.ui.lead_workspace', ['enabled' => true, 'users' => [$sales->id], 'roles' => []]);
        $this->actingAs($sales);
        $this->assertTrue(app(FeatureFlag::class)->enabled('ui.lead_workspace', $sales));
        $this->assertTrue(SalesWorkspace::canAccess());

        Livewire::test(SalesWorkspace::class)->assertSee('Active leads')->assertSee($lead->reference_id)->assertDontSee($completed->reference_id);
        Livewire::test(MyWork::class)->assertSee('Prioritized queue');
        Livewire::test(SalesPipeline::class)->assertSee('Cards are read-only');
        Livewire::withQueryParams(['lead' => $lead->id])->test(LeadWorkspace::class)->assertSee($lead->reference_id);
    }

    #[Test]
    public function non_pilot_user_cannot_access_workspace_surfaces(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        config()->set('workflow.ui.lead_workspace', ['enabled' => true, 'users' => [999], 'roles' => []]);

        $this->actingAs($sales)->get(SalesWorkspace::getUrl())->assertForbidden();
    }
}
