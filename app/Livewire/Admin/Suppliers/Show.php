<?php
namespace App\Livewire\Admin\Suppliers;
use App\Models\Supplier; use Livewire\Component;
class Show extends Component{public Supplier $supplier;public function mount():void{abort_unless(auth()->user()->isAdmin()||auth()->user()->isAccount()||auth()->user()->hasPermission('suppliers.view'),403);$this->supplier->load(['vendorBills.invoice.lead','vendorBills.vendorBillPayments','supplierPayments.allocations.vendorBill','supplierPayments.creator']);}public function render(){return view('livewire.admin.suppliers.show')->layout('components.layouts.admin',['title'=>$this->supplier->name]);}}
