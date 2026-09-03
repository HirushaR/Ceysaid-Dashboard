<?php
namespace App\Livewire\Admin\Payments;
use App\Models\SupplierPayment; use Livewire\Component;
class SupplierShow extends Component{public SupplierPayment $supplierPayment;public function mount():void{abort_unless(auth()->user()->canManageAccountingRecords(),403);$this->supplierPayment->load(['supplier','creator','allocations.vendorBill.invoice.lead']);}public function render(){return view('livewire.admin.payments.supplier-show')->layout('components.layouts.admin',['title'=>$this->supplierPayment->payment_number]);}}
