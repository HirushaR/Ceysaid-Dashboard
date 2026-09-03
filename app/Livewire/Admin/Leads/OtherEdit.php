<?php

namespace App\Livewire\Admin\Leads;

use App\Enums\OtherLeadStatus;
use App\Models\Lead;
use Livewire\Component;

class OtherEdit extends Component
{
    public Lead $lead;
    public string $customer_name=''; public string $contact_method='phone'; public string $contact_value=''; public string $subject='';
    public ?string $other_lead_start_date=null; public ?string $other_lead_end_date=null; public string $other_lead_details='';
    public function mount():void
    {
        abort_unless($this->lead->is_other_lead && auth()->user()->isSales() && $this->lead->created_by===auth()->id() && $this->lead->other_lead_status!==OtherLeadStatus::Completed,403);
        $this->fill($this->lead->only(['customer_name','contact_method','contact_value','subject','other_lead_details']));
        $this->other_lead_start_date=$this->lead->other_lead_start_date?->format('Y-m-d'); $this->other_lead_end_date=$this->lead->other_lead_end_date?->format('Y-m-d');
    }
    public function save()
    {
        $data=$this->validate(['customer_name'=>['required','string','max:255'],'contact_method'=>['nullable','in:phone,email,whatsapp,facebook'],'contact_value'=>['nullable','string','max:255'],'subject'=>['nullable','string','max:255'],'other_lead_start_date'=>['nullable','date'],'other_lead_end_date'=>['nullable','date','after_or_equal:other_lead_start_date'],'other_lead_details'=>['nullable','string','max:10000']]);
        $this->lead->update($data); session()->flash('success','Other lead updated.'); return $this->redirectRoute('admin.leads.show',$this->lead,navigate:true);
    }
    public function render(){ return view('livewire.admin.leads.other-form',['heading'=>'Edit other lead'])->layout('components.layouts.admin',['title'=>'Edit other lead']); }
}
