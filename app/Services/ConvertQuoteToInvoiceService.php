<?php

namespace App\Services;

use App\Enums\QuoteStatus;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\Quote;
use Illuminate\Support\Facades\DB;

class ConvertQuoteToInvoiceService
{
    public function __construct(
        private DocumentNumberService $numbers
    ) {}

    public function convert(Quote $quote): Invoice
    {
        if ($quote->isConverted()) {
            throw new \InvalidArgumentException('Quote is already converted to an invoice.');
        }
        if ($quote->status !== QuoteStatus::Accepted) {
            throw new \InvalidArgumentException('Only an accepted quote can be converted to an invoice.');
        }

        return DB::transaction(function () use ($quote) {
            $quote->load('lineItems', 'lead');
            $total = $quote->totalAmount();

            $invoice = Invoice::create([
                'lead_id' => $quote->lead_id,
                'quote_id' => $quote->id,
                'invoice_number' => $this->numbers->nextInvoiceNumberForLead($quote->lead_id),
                'invoice_date' => now()->toDateString(),
                'due_date' => $quote->valid_until?->toDateString() ?? now()->toDateString(),
                'terms' => $quote->terms,
                'subject' => $quote->subject,
                'total_amount' => $total,
                'description' => $quote->notes,
                'notes' => $quote->notes,
            ]);

            $sort = 0;
            foreach ($quote->lineItems as $line) {
                InvoiceLineItem::create([
                    'invoice_id' => $invoice->id,
                    'lead_cost_id' => null,
                    'sort_order' => $sort++,
                    'description' => $line->description,
                    'customer_details' => null,
                    'quantity' => $line->quantity,
                    'rate' => $line->rate,
                    'amount' => $line->amount,
                ]);
            }

            $quote->update(['status' => QuoteStatus::Converted]);
            $quote->actionLogs()->create([
                'user_id' => auth()->id(),
                'action' => 'converted',
                'after' => ['invoice_id' => $invoice->id],
            ]);

            return $invoice->fresh(['lineItems']);
        });
    }
}
