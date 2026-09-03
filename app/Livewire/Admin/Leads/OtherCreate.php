<?php

namespace App\Livewire\Admin\Leads;

use App\Models\Lead;
use App\Services\LeadWorkflowService;
use Livewire\Component;

class OtherCreate extends Component
{
    public string $customer_name=''; public string $contact_method='phone'; public string $contact_value=''; public string $subject='';
    public ?string $other_lead_start_date=null; public ?string $other_lead_end_date=null; public string $other_lead_details='';

    public function mount():void { abort_unless(auth()->user()->isSales(),403); }
    public function save(LeadWorkflowService $workflow)
    {
        $data=$this->validate(['customer_name'=>['required','string','max:255'],'contact_method'=>['nullable','in:phone,email,whatsapp,facebook'],'contact_value'=>['nullable','string','max:255'],'subject'=>['nullable','string','max:255'],'other_lead_start_date'=>['nullable','date'],'other_lead_end_date'=>['nullable','date','after_or_equal:other_lead_start_date'],'other_lead_details'=>['nullable','string','max:10000']]);
        $lead=$workflow->create($data+['is_other_lead'=>true],auth()->user());
        $lead->update(['reference_id'=>'OL/'.now()->year.'/'.$lead->id]);
        session()->flash('success','Other lead created.');
        return $this->redirectRoute('admin.leads.show',$lead,navigate:true);
    }
    public function render(){ return view('livewire.admin.leads.other-form',['heading'=>'New other lead'])->layout('components.layouts.admin',['title'=>'New other lead']); }
}
