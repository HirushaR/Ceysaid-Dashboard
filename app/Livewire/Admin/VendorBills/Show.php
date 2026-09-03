<?php
namespace App\Livewire\Admin\VendorBills;
use App\Models\VendorBill; use Livewire\Component;
class Show extends Component{public VendorBill $vendorBill;public function mount():void{abort_unless(auth()->user()->can('view',$this->vendorBill),403);$this->vendorBill->load(['supplier','invoice.lead','lineItems','vendorBillPayments.supplierPayment']);}public function render(){return view('livewire.admin.vendor-bills.show')->layout('components.layouts.admin',['title'=>$this->vendorBill->vendor_bill_number]);}}
