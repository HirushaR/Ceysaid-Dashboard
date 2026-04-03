<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;

class CreateInvoiceForConfirmedLeadService
{
    public function __construct(
        private DocumentNumberService $numbers
    ) {}

    public function createIfNeeded(Lead $lead): ?Invoice
    {
        if ($lead->invoices()->exists()) {
            return null;
        }

        return DB::transaction(function () use ($lead) {
            $lead->load('leadCosts');
            $invoice = Invoice::create([
                'lead_id' => $lead->id,
                'quote_id' => null,
                'invoice_number' => $this->numbers->nextInvoiceNumberForLead($lead->id),
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->toDateString(),
                'terms' => 'Due on Receipt',
                'subject' => $lead->subject,
                'total_amount' => 0,
                'description' => trim(($lead->reference_id ? "Ref: {$lead->reference_id}" : '').' '.($lead->tour ?? '')),
                'notes' => null,
            ]);

            $sort = 0;
            $total = 0.0;

            if ($lead->leadCosts->isEmpty()) {
                InvoiceLineItem::create([
                    'invoice_id' => $invoice->id,
                    'lead_cost_id' => null,
                    'sort_order' => $sort,
                    'description' => 'Pending pricing',
                    'customer_details' => $lead->customer_name,
                    'quantity' => 1,
                    'rate' => 0,
                    'amount' => 0,
                ]);
            } else {
                foreach ($lead->leadCosts as $cost) {
                    $amt = (float) $cost->amount;
                    $total += $amt;
                    InvoiceLineItem::create([
                        'invoice_id' => $invoice->id,
                        'lead_cost_id' => $cost->id,
                        'sort_order' => $sort++,
                        'description' => $cost->details ?: ($cost->invoice_number ?? 'Service'),
                        'customer_details' => $lead->customer_name,
                        'quantity' => 1,
                        'rate' => $amt,
                        'amount' => $amt,
                    ]);
                }
                $invoice->update(['total_amount' => $total]);
            }

            return $invoice->fresh(['lineItems']);
        });
    }
}
