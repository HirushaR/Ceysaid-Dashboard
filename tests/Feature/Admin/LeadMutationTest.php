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
        $sales=User::factory()->create(['role'=>'sales']); $this->actingAs($sales);
        Livewire::test(OtherCreate::class)->set('customer_name','Standalone Customer')->set('subject','Hotel only')->call('save')->assertHasNoErrors();
        $lead=Lead::where('customer_name','Standalone Customer')->firstOrFail();
        $this->assertSame('OL/'.now()->year.'/'.$lead->id,$lead->reference_id); $this->assertSame(OtherLeadStatus::Draft,$lead->other_lead_status);
        Livewire::test(OtherEdit::class,['lead'=>$lead])->set('other_lead_details','Updated details')->call('save')->assertHasNoErrors();
        $this->assertSame('Updated details',$lead->fresh()->other_lead_details);
        Livewire::test(Show::class,['lead'=>$lead->fresh()])->call('transitionOther','confirmed')->assertHasNoErrors();
        $this->assertSame(OtherLeadStatus::Confirmed,$lead->fresh()->other_lead_status);
        Livewire::test(Show::class,['lead'=>$lead->fresh()])->call('transitionOther','completed')->assertHasNoErrors();
        $this->assertSame(OtherLeadStatus::Completed,$lead->fresh()->other_lead_status);
        $this->get(route('admin.leads.show',$lead))->assertOk()->assertSee('Hotel only');
    }

    public function test_admin_can_edit_details_assignments_and_upload_attachment(): void
    {
        Storage::fake('lead-attachments'); $admin=User::factory()->create(['role'=>'admin']); $sales=User::factory()->create(['role'=>'sales']); $lead=Lead::factory()->create(['status'=>LeadStatus::NEW->value]); $this->actingAs($admin);
        Livewire::test(Edit::class,['lead'=>$lead])->set('customer_name','Updated Traveller')->set('assigned_to',$sales->id)->call('save')->assertHasNoErrors();
        $this->assertSame('Updated Traveller',$lead->fresh()->customer_name); $this->assertSame($sales->id,$lead->fresh()->assigned_to);
        Livewire::test(Edit::class,['lead'=>$lead->fresh()])->set('uploads',[UploadedFile::fake()->create('passport.pdf',100,'application/pdf')])->set('upload_type','passport')->call('uploadFiles')->assertHasNoErrors();
        $attachment=$lead->attachments()->firstOrFail(); Storage::disk('lead-attachments')->assertExists($attachment->file_path); $this->assertSame($admin->id,$attachment->uploaded_by);
    }

    public function test_assigned_operator_can_edit_service_status_but_not_customer_details(): void
    {
        $ops=User::factory()->create(['role'=>'operation']); $lead=Lead::factory()->create(['assigned_operator'=>$ops->id,'status'=>LeadStatus::ASSIGNED_TO_OPERATIONS->value,'customer_name'=>'Original']); $this->actingAs($ops);
        Livewire::test(Edit::class,['lead'=>$lead])->set('hotel_status','done')->set('customer_name','Not allowed')->call('save')->assertHasNoErrors();
        $lead->refresh(); $this->assertSame('done',$lead->hotel_status); $this->assertSame('Original',$lead->customer_name);
    }
}
