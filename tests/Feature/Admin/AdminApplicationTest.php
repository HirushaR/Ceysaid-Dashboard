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
        $this->actingAs($user)->get(route('admin.leads.show',$lead))->assertOk()->assertSee('Test Traveller')->assertSee('Activity');
    }
}
