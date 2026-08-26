<?php

namespace Tests\Feature\Workflow;

use App\Enums\LeadStatus;
use App\Enums\OtherLeadStatus;
use App\Models\Lead;
use App\Models\User;
use App\Services\LeadWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LeadWorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_created_lead_is_assigned_to_creator(): void
    {
        $sales=User::factory()->create(['role'=>'sales']);
        $lead=app(LeadWorkflowService::class)->create(['customer_name'=>'Customer'], $sales);
        $this->assertSame(LeadStatus::ASSIGNED_TO_SALES->value,$lead->status);
        $this->assertSame($sales->id,$lead->assigned_to);
    }

    public function test_call_centre_created_lead_is_new_and_unassigned(): void
    {
        $user=User::factory()->create(['role'=>'call_center']);
        $lead=app(LeadWorkflowService::class)->create(['customer_name'=>'Customer'], $user);
        $this->assertSame(LeadStatus::NEW->value,$lead->status);
        $this->assertNull($lead->assigned_to);
    }

    public function test_other_lead_uses_separate_workflow(): void
    {
        $sales=User::factory()->create(['role'=>'sales']);
        $lead=app(LeadWorkflowService::class)->create(['customer_name'=>'Other','is_other_lead'=>true],$sales);
        $this->assertSame(LeadStatus::ASSIGNED_TO_SALES->value,$lead->status);
        $this->assertSame(OtherLeadStatus::Draft,$lead->other_lead_status);
        $this->expectException(ValidationException::class);
        app(LeadWorkflowService::class)->transition($lead,LeadStatus::INFO_GATHER_COMPLETE,$sales);
    }

    public function test_sales_and_operations_handoff_is_role_guarded(): void
    {
        $sales=User::factory()->create(['role'=>'sales']); $ops=User::factory()->create(['role'=>'operation']);
        $lead=Lead::factory()->create(['status'=>LeadStatus::ASSIGNED_TO_SALES->value,'assigned_to'=>$sales->id]);
        $lead=app(LeadWorkflowService::class)->transition($lead,LeadStatus::INFO_GATHER_COMPLETE,$sales);
        $lead=app(LeadWorkflowService::class)->transition($lead,LeadStatus::ASSIGNED_TO_OPERATIONS,$ops);
        $this->assertSame($ops->id,$lead->assigned_operator);
        $this->assertSame(LeadStatus::ASSIGNED_TO_OPERATIONS->value,$lead->status);
    }
}
