<?php

namespace App\Livewire\Admin\Quotes;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Services\ConvertQuoteToInvoiceService;
use App\Services\QuoteWorkflowService;
use Livewire\Component;

class Show extends Component
{
    public Quote $quote;
    public function mount(): void { abort_unless(auth()->user()->canViewQuote($this->quote),403); $this->refreshQuote(); }
    private function refreshQuote(): void { $this->quote->load(['lead','lineItems','invoices','revisions','actionLogs.user']); }
    public function transition(string $status, QuoteWorkflowService $workflow): void { $this->quote=$workflow->transition($this->quote,QuoteStatus::from($status),auth()->user()); $this->refreshQuote(); }
    public function revise(QuoteWorkflowService $workflow) { $copy=$workflow->revise($this->quote,auth()->user()); return $this->redirectRoute('admin.quotes.show',$copy,navigate:true); }
    public function convert(ConvertQuoteToInvoiceService $service) { $invoice=$service->convert($this->quote); session()->flash('success','Invoice created from accepted quote.'); return $this->redirectRoute('admin.invoices.show',$invoice,navigate:true); }
    public function render() { return view('livewire.admin.quotes.show')->layout('components.layouts.admin',['title'=>$this->quote->quote_number]); }
}
