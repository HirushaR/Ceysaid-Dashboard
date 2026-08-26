<?php

namespace Tests\Feature\Admin;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_the_new_admin_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/login')->assertOk()->assertSee('Welcome back');
    }

    public function test_authenticated_user_can_render_dashboard_and_leads(): void
    {
        $user=User::factory()->create(['role'=>'admin']); Lead::factory()->create();
        $this->actingAs($user)->get('/admin')->assertOk()->assertSee('Recent leads');
        $this->actingAs($user)->get('/admin/leads')->assertOk()->assertSee('Manage sales and operations');
    }

    public function test_lead_details_render_in_new_application(): void
    {
        $user=User::factory()->create(['role'=>'admin']); $lead=Lead::factory()->create(['customer_name'=>'Test Traveller']);
        $this->actingAs($user)->get(route('admin.leads.show',$lead))->assertOk()->assertSee('Test Traveller')->assertSee('Recent timeline');
    }

    public function test_sales_pipeline_groups_visible_leads_into_columns(): void
    {
        $user=User::factory()->create(['role'=>'admin']);
        Lead::factory()->create(['customer_name'=>'Pipeline Customer','status'=>\App\Enums\LeadStatus::ASSIGNED_TO_SALES->value]);
        $this->actingAs($user)->get(route('admin.leads.pipeline'))->assertOk()->assertSee('Sales Pipeline')->assertSee('Pipeline Customer')->assertSee('Assigned to Sales');
    }

    public function test_sales_dashboard_tabs_apply_their_filters(): void
    {
        $sales=User::factory()->create(['role'=>'sales']);
        Lead::factory()->otherLead()->create(['created_by'=>$sales->id,'customer_name'=>'Other Customer']);
        Lead::factory()->create(['assigned_to'=>$sales->id,'is_group_lead'=>true,'status'=>\App\Enums\LeadStatus::ASSIGNED_TO_SALES->value,'customer_name'=>'Group Customer']);
        $this->actingAs($sales)->get(route('admin.dashboard.other'))->assertOk()->assertSee('Other Customer')->assertDontSee('Group Customer');
        $this->actingAs($sales)->get(route('admin.dashboard.group'))->assertOk()->assertSee('Group Customer')->assertDontSee('Other Customer');
    }

    public function test_admin_cannot_open_sales_only_dashboard_tab(): void
    {
        $admin=User::factory()->create(['role'=>'admin']);
        $this->actingAs($admin)->get(route('admin.dashboard.my-sales'))->assertForbidden();
    }
}
