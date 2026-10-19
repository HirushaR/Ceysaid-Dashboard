<?php

namespace Tests\Feature\Admin;

use App\Enums\LeadStatus;
use App\Enums\OtherLeadStatus;
use App\Livewire\Admin\Leads\Edit;
use App\Livewire\Admin\Leads\OtherCreate;
use App\Livewire\Admin\Leads\OtherEdit;
use App\Livewire\Admin\Leads\Show;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class LeadMutationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_user_can_create_edit_confirm_and_complete_other_lead(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $this->actingAs($sales);
        Livewire::test(OtherCreate::class)->set('customer_name', 'Standalone Customer')->set('subject', 'Hotel only')->call('save')->assertHasNoErrors();
        $lead = Lead::where('customer_name', 'Standalone Customer')->firstOrFail();
        $this->assertSame('OL/'.now()->year.'/'.$lead->id, $lead->reference_id);
        $this->assertSame(OtherLeadStatus::Draft, $lead->other_lead_status);
        Livewire::test(OtherEdit::class, ['lead' => $lead])->set('other_lead_details', 'Updated details')->call('save')->assertHasNoErrors();
        $this->assertSame('Updated details', $lead->fresh()->other_lead_details);
        Livewire::test(Show::class, ['lead' => $lead->fresh()])->call('transitionOther', 'confirmed')->assertHasNoErrors();
        $this->assertSame(OtherLeadStatus::Confirmed, $lead->fresh()->other_lead_status);
        Livewire::test(Show::class, ['lead' => $lead->fresh()])->call('transitionOther', 'completed')->assertHasNoErrors();
        $this->assertSame(OtherLeadStatus::Completed, $lead->fresh()->other_lead_status);
        $this->get(route('admin.leads.show', $lead))->assertOk()->assertSee('Hotel only');
    }

    public function test_admin_can_edit_details_assignments_and_upload_attachment(): void
    {
        Storage::fake('lead-attachments');
        $admin = User::factory()->create(['role' => 'admin']);
        $sales = User::factory()->create(['role' => 'sales']);
        $lead = Lead::factory()->create(['status' => LeadStatus::NEW->value]);
        $this->actingAs($admin);
        Livewire::test(Edit::class, ['lead' => $lead])->set('customer_name', 'Updated Traveller')->set('tour', 'TOUR-REF-2026')->set('assigned_to', $sales->id)->call('save')->assertHasNoErrors();
        $this->assertSame('Updated Traveller', $lead->fresh()->customer_name);
        $this->assertSame('TOUR-REF-2026', $lead->fresh()->tour);
        $this->assertSame($sales->id, $lead->fresh()->assigned_to);
        Livewire::test(Edit::class, ['lead' => $lead->fresh()])->set('uploads', [UploadedFile::fake()->create('passport.pdf', 100, 'application/pdf')])->set('upload_type', 'passport')->call('uploadFiles')->assertHasNoErrors();
        $attachment = $lead->attachments()->firstOrFail();
        Storage::disk('lead-attachments')->assertExists($attachment->file_path);
        $this->assertSame($admin->id, $attachment->uploaded_by);
    }

    public function test_assigned_operator_can_edit_service_status_but_not_customer_details(): void
    {
        $ops = User::factory()->create(['role' => 'operation']);
        $lead = Lead::factory()->create(['assigned_operator' => $ops->id, 'status' => LeadStatus::ASSIGNED_TO_OPERATIONS->value, 'customer_name' => 'Original']);
        $this->actingAs($ops);
        Livewire::test(Edit::class, ['lead' => $lead])->set('hotel_status', 'done')->set('customer_name', 'Not allowed')->call('save')->assertHasNoErrors();
        $lead->refresh();
        $this->assertSame('done', $lead->hotel_status);
        $this->assertSame('Original', $lead->customer_name);
    }

    public function test_lead_with_nullable_legacy_fields_can_be_opened_and_edited(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lead = Lead::factory()->create();
        $lead->update([
            'platform' => null,
            'contact_method' => null,
            'contact_value' => null,
            'destination' => null,
            'message' => null,
            'priority' => null,
        ]);
        $this->actingAs($admin);

        Livewire::test(Edit::class, ['lead' => $lead->fresh()])
            ->assertSet('destination', '')
            ->assertSet('platform', 'other')
            ->assertSet('priority', 'medium')
            ->set('customer_name', 'Legacy Lead Updated')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Legacy Lead Updated', $lead->fresh()->customer_name);
    }

    public function test_operation_user_only_sees_reference_until_claiming_a_lead(): void
    {
        $operator = User::factory()->create(['role' => 'operation']);
        $lead = Lead::factory()->create([
            'status' => LeadStatus::INFO_GATHER_COMPLETE->value,
            'assigned_operator' => null,
            'customer_name' => 'Hidden Traveller',
            'contact_value' => '+61400000000',
            'destination' => 'Tokyo',
        ]);
        $this->actingAs($operator);

        Livewire::test(Show::class, ['lead' => $lead])
            ->assertSee($lead->reference_id)
            ->assertSee('Assign to me')
            ->assertDontSee('Hidden Traveller')
            ->assertDontSee('+61400000000')
            ->assertDontSee('Tokyo')
            ->call('transition', LeadStatus::ASSIGNED_TO_OPERATIONS->value)
            ->assertHasNoErrors()
            ->assertSee('Hidden Traveller')
            ->assertSee('Tokyo');

        $this->assertSame($operator->id, $lead->fresh()->assigned_operator);
    }

    public function test_operation_user_cannot_open_another_operators_lead(): void
    {
        $operator = User::factory()->create(['role' => 'operation']);
        $otherOperator = User::factory()->create(['role' => 'operation']);
        $lead = Lead::factory()->create([
            'status' => LeadStatus::ASSIGNED_TO_OPERATIONS->value,
            'assigned_operator' => $otherOperator->id,
        ]);

        $this->actingAs($operator);

        Livewire::test(Show::class, ['lead' => $lead])->assertForbidden();
    }

    public function test_sales_user_cannot_open_another_users_other_lead(): void
    {
        $owner = User::factory()->create(['role' => 'sales']);
        $otherSales = User::factory()->create(['role' => 'sales']);
        $lead = Lead::factory()->create([
            'is_other_lead' => true,
            'created_by' => $owner->id,
            'assigned_to' => $owner->id,
        ]);

        $this->actingAs($otherSales);

        Livewire::test(Show::class, ['lead' => $lead])->assertForbidden();
    }

    public function test_internal_note_notifies_the_other_assigned_team_member(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $operator = User::factory()->create(['role' => 'operation']);
        $lead = Lead::factory()->create([
            'assigned_to' => $sales->id,
            'assigned_operator' => $operator->id,
            'created_by' => $sales->id,
            'status' => LeadStatus::ASSIGNED_TO_OPERATIONS->value,
        ]);
        $this->actingAs($sales);

        Livewire::test(Show::class, ['lead' => $lead])
            ->set('note', 'Please reconfirm the hotel before ticketing.')
            ->call('addNote')
            ->assertHasNoErrors();

        $notification = $operator->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertSame('New internal lead note', $notification->data['title']);
        $this->assertSame($lead->id, $notification->data['lead_id']);
        $this->assertCount(0, $sales->notifications);
    }
}
