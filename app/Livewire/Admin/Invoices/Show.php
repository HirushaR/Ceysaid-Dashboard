<?php

namespace App\Livewire\Admin\Invoices;

use App\Models\Invoice;
use Livewire\Component;

class Show extends Component
{
    public Invoice $invoice;
    public function mount():void { abort_unless(auth()->user()->canViewInvoice($this->invoice),403); $this->invoice->load(['lead','quote','lineItems','customerPayments','vendorBills.supplier']); }
    public function render(){ $logs=$this->invoice->lead?->actionLogs()->with('user')->whereIn('action',['invoice_created','invoice_updated'])->where(fn($q)=>$q->where('new_values->invoice_id',$this->invoice->id)->orWhere('description','like','%'.$this->invoice->invoice_number.'%'))->latest()->limit(20)->get()??collect(); return view('livewire.admin.invoices.show',compact('logs'))->layout('components.layouts.admin',['title'=>$this->invoice->invoice_number]); }
}
