<?php
namespace App\Livewire\Admin\Payments;
use App\Models\CustomerPayment; use Livewire\Component;
class CustomerShow extends Component{public CustomerPayment $customerPayment;public function mount():void{$this->customerPayment->load('invoice.lead');abort_unless(auth()->user()->canViewInvoice($this->customerPayment->invoice),403);}public function render(){return view('livewire.admin.payments.customer-show')->layout('components.layouts.admin',['title'=>$this->customerPayment->receipt_number]);}}
