<?php

namespace App\Livewire\Admin\VendorBills;

use App\Models\Invoice;
use App\Models\Supplier;
use App\Models\VendorBill;
use App\Models\VendorBillLineItem;
use App\Services\DocumentNumberService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Create extends Component
{
    public ?int $invoice_id = null; public ?int $supplier_id = null; public ?string $due_date = null;
    public string $service_type = ''; public string $service_details = ''; public string $notes = '';
    public array $lines = [['description' => '', 'quantity' => 1, 'rate' => 0]];
    public function mount(): void { abort_unless(auth()->user()->canManageAccountingRecords(),403); $this->invoice_id = request()->integer('invoice') ?: null; $this->due_date = now()->toDateString(); }
    public function addLine(): void { $this->lines[] = ['description'=>'','quantity'=>1,'rate'=>0]; }
    public function removeLine(int $index): void { if(count($this->lines)>1){ unset($this->lines[$index]); $this->lines=array_values($this->lines); } }
    public function save(DocumentNumberService $numbers)
    {
        $data=$this->validate(['invoice_id'=>['required','exists:invoices,id'],'supplier_id'=>['required','exists:suppliers,id'],'due_date'=>['required','date'],'service_type'=>['required','string','max:255'],'service_details'=>['nullable','string','max:5000'],'notes'=>['nullable','string','max:5000'],'lines'=>['required','array','min:1'],'lines.*.description'=>['required','string','max:2000'],'lines.*.quantity'=>['required','numeric','gt:0'],'lines.*.rate'=>['required','numeric','min:0']]);
        $bill=DB::transaction(function()use($data,$numbers){ $supplier=Supplier::findOrFail($data['supplier_id']); $total=VendorBillLineItem::sumAmountsFromFormArray($data['lines']); $bill=VendorBill::create(['invoice_id'=>$data['invoice_id'],'supplier_id'=>$supplier->id,'vendor_name'=>$supplier->name,'vendor_bill_number'=>$numbers->nextVendorBillNumber(),'bill_amount'=>$total,'due_date'=>$data['due_date'],'service_type'=>$data['service_type'],'service_details'=>$data['service_details'],'notes'=>$data['notes'],'payment_status'=>'pending']); foreach($data['lines'] as $i=>$line)$bill->lineItems()->create($line+['sort_order'=>$i]); return $bill; });
        session()->flash('success','Vendor bill created.'); return $this->redirectRoute('admin.vendor-bills.index',navigate:true);
    }
    public function render(){return view('livewire.admin.vendor-bills.form',['invoices'=>Invoice::with('lead')->latest()->limit(200)->get(),'suppliers'=>Supplier::orderBy('name')->get(),'heading'=>'New vendor bill','submitLabel'=>'Create bill'])->layout('components.layouts.admin',['title'=>'New vendor bill']);}
}
