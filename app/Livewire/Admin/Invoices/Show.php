<?php

namespace App\Livewire\Admin\Invoices;

use App\Models\Invoice;
use Livewire\Component;

class Show extends Component
{
    public Invoice $invoice;
    public function mount():void { abort_unless(auth()->user()->canViewInvoice($this->invoice),403); $this->invoice->load(['lead','quote','lineItems','customerPayments','vendorBills.supplier']); }
    public function render(){ return view('livewire.admin.invoices.show')->layout('components.layouts.admin',['title'=>$this->invoice->invoice_number]); }
}
