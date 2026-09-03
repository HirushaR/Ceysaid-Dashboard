<?php

namespace App\Livewire\Admin\Leads;

use App\Enums\Platform;
use App\Enums\Priority;
use App\Enums\ServiceStatus;
use App\Models\Attachment;
use App\Models\Lead;
use App\Models\Tour;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class Edit extends Component
{
    use WithFileUploads;

    public Lead $lead;
    public string $customer_name=''; public string $platform='other'; public string $contact_method='phone'; public string $contact_value=''; public string $destination=''; public string $message=''; public string $priority='medium';
    public ?string $arrival_date=null; public ?string $depature_date=null; public int $number_of_adults=1; public int $number_of_children=0; public int $number_of_infants=0; public bool $is_group_lead=false; public bool $is_cruise_lead=false; public ?int $tour_id=null;
    public ?int $assigned_to=null; public ?int $assigned_operator=null;
    public string $air_ticket_status='pending'; public string $hotel_status='pending'; public string $visa_status='pending'; public string $land_package_status='pending';
    public array $uploads=[]; public string $upload_type='document';

    public function mount():void
    {
        abort_if($this->lead->is_other_lead,404); abort_unless($this->canEditDetails()||$this->canEditServices(),403);
        $this->fill($this->lead->only(['customer_name','platform','contact_method','contact_value','destination','message','priority','number_of_adults','number_of_children','number_of_infants','is_group_lead','is_cruise_lead','tour_id','assigned_to','assigned_operator','air_ticket_status','hotel_status','visa_status','land_package_status']));
        $this->arrival_date=$this->lead->arrival_date?->format('Y-m-d'); $this->depature_date=$this->lead->depature_date?->format('Y-m-d');
    }

    public function canEditDetails():bool
    {
        $user=auth()->user(); return $user->isAdmin()||($user->isSales()&&($this->lead->assigned_to===$user->id||$this->lead->created_by===$user->id)&&$user->canEditResource('leads'));
    }
    public function canEditServices():bool { $user=auth()->user(); return $user->isAdmin()||($user->isOperation()&&$this->lead->assigned_operator===$user->id); }
    public function canAssign():bool { return auth()->user()->isAdmin(); }

    public function save()
    {
        $data=[];
        if($this->canEditDetails()) $data += $this->validate(['customer_name'=>['required','string','max:255'],'platform'=>['required','in:'.implode(',',array_column(Platform::cases(),'value'))],'contact_method'=>['nullable','string','max:32'],'contact_value'=>['nullable','string','max:255'],'destination'=>['nullable','string','max:255'],'message'=>['nullable','string','max:10000'],'priority'=>['required','in:'.implode(',',array_column(Priority::cases(),'value'))],'arrival_date'=>['nullable','date'],'depature_date'=>['nullable','date','after_or_equal:arrival_date'],'number_of_adults'=>['required','integer','min:1'],'number_of_children'=>['required','integer','min:0'],'number_of_infants'=>['required','integer','min:0'],'is_group_lead'=>['boolean'],'is_cruise_lead'=>['boolean'],'tour_id'=>['nullable','exists:tours,id']]);
        if($this->canEditServices()) $data += $this->validate(['air_ticket_status'=>['required','in:'.implode(',',array_column(ServiceStatus::cases(),'value'))],'hotel_status'=>['required','in:'.implode(',',array_column(ServiceStatus::cases(),'value'))],'visa_status'=>['required','in:'.implode(',',array_column(ServiceStatus::cases(),'value'))],'land_package_status'=>['required','in:'.implode(',',array_column(ServiceStatus::cases(),'value'))]]);
        if($this->canAssign()) $data += $this->validate(['assigned_to'=>['nullable','exists:users,id'],'assigned_operator'=>['nullable','exists:users,id']]);
        if(!empty($data['is_cruise_lead'])) $data['is_group_lead']=false; elseif(!empty($data['is_group_lead'])) $data['is_cruise_lead']=false; else $data['tour_id']=null;
        $this->lead->update($data); session()->flash('success','Lead updated.'); return $this->redirectRoute('admin.leads.show',$this->lead,navigate:true);
    }

    public function uploadFiles():void
    {
        abort_unless($this->canEditDetails()||$this->canEditServices(),403);
        $this->validate(['uploads'=>['required','array','min:1','max:10'],'uploads.*'=>['file','mimes:jpg,jpeg,png,pdf,doc,docx,txt','max:10240'],'upload_type'=>['required','in:document,passport,visa,ticket,hotel,other']]);
        foreach($this->uploads as $file){ $name=now()->format('Y-m-d_H-i-s').'_'.Str::random(6).'_'.$file->getClientOriginalName(); $path=$file->storeAs('',$name,'lead-attachments'); Attachment::create(['lead_id'=>$this->lead->id,'file_path'=>$path,'original_name'=>$file->getClientOriginalName(),'type'=>$this->upload_type,'uploaded_by'=>auth()->id()]); }
        $this->reset('uploads'); $this->lead->load('attachments'); session()->flash('success','Files uploaded.');
    }

    public function render()
    {
        return view('livewire.admin.leads.edit',['platforms'=>Platform::options(),'priorities'=>Priority::options(),'serviceStatuses'=>ServiceStatus::options(),'tours'=>Tour::query()->orderByDesc('departure_date')->get(),'salesUsers'=>User::query()->where('role','sales')->orderBy('name')->get(),'operationUsers'=>User::query()->where('role','operation')->orderBy('name')->get()])->layout('components.layouts.admin',['title'=>'Edit lead']);
    }
}
