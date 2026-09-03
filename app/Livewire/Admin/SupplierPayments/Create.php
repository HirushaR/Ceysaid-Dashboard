<?php

namespace App\Livewire\Admin\SupplierPayments;

use App\Enums\DepositAccount;
use App\Enums\PaymentMode;
use App\Models\Supplier;
use App\Models\VendorBill;
use App\Services\RecordSupplierPaymentService;
use Livewire\Component;

class Create extends Component
{
    public ?int $supplier_id=null; public string $payment_date=''; public $amount=null; public string $payment_mode='bank_transfer'; public string $paid_through='cash'; public string $reference_number=''; public string $notes=''; public array $allocations=[];
    public function mount():void{abort_unless(auth()->user()->canManageAccountingRecords(),403);$this->payment_date=now()->toDateString();$this->supplier_id=request()->integer('supplier')?:null;if($this->supplier_id)$this->loadBills();}
    public function updatedSupplierId():void{$this->loadBills();}
    private function loadBills():void{$this->allocations=VendorBill::query()->where('supplier_id',$this->supplier_id)->with('vendorBillPayments')->get()->filter(fn($b)=>$b->outstanding_amount>0)->map(fn($b)=>['vendor_bill_id'=>$b->id,'number'=>$b->vendor_bill_number,'outstanding'=>$b->outstanding_amount,'amount'=>0])->values()->all();}
    public function useOutstanding(int $index):void{$this->allocations[$index]['amount']=$this->allocations[$index]['outstanding'];$this->amount=collect($this->allocations)->sum(fn($a)=>(float)$a['amount']);}
    public function save(RecordSupplierPaymentService $service)
    {
        $data=$this->validate(['supplier_id'=>['required','exists:suppliers,id'],'payment_date'=>['required','date','before_or_equal:today'],'amount'=>['required','numeric','gt:0'],'payment_mode'=>['required','in:'.implode(',',array_keys(PaymentMode::options()))],'paid_through'=>['required','in:'.implode(',',array_keys(DepositAccount::options()))],'reference_number'=>['nullable','string','max:255'],'notes'=>['nullable','string','max:5000'],'allocations'=>['required','array'],'allocations.*.vendor_bill_id'=>['required','integer'],'allocations.*.amount'=>['nullable','numeric','min:0']]);
        $data['allocations']=collect($data['allocations'])->filter(fn($a)=>(float)$a['amount']>0)->map(fn($a)=>['vendor_bill_id'=>$a['vendor_bill_id'],'amount'=>$a['amount']])->values()->all();
        $service->record($data); session()->flash('success','Supplier payment recorded and allocated.'); return $this->redirectRoute('admin.payments.index',navigate:true);
    }
    public function render(){return view('livewire.admin.supplier-payments.create',['suppliers'=>Supplier::orderBy('name')->get(),'modes'=>PaymentMode::options(),'accounts'=>DepositAccount::options()])->layout('components.layouts.admin',['title'=>'Supplier payment']);}
}
