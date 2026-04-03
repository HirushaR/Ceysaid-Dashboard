<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Services\LeadActionLogger;

class InvoiceObserver
{
    /** @var list<string> */
    private const IGNORE_ON_UPDATE = [
        'customer_payment_status',
        'balance_amount',
        'payment_amount',
        'payment_date',
        'vendor_payment_status',
        'updated_at',
    ];

    public function created(Invoice $invoice): void
    {
        $invoice->loadMissing('lead');
        LeadActionLogger::log(
            $invoice->lead,
            'invoice_created',
            'Invoice '.($invoice->invoice_number ?? '#'.$invoice->id).' created — LKR '.number_format((float) $invoice->total_amount, 2),
            null,
            [
                'invoice_number' => $invoice->invoice_number,
                'total_amount' => $invoice->total_amount,
                'invoice_id' => $invoice->id,
            ]
        );
    }

    public function updated(Invoice $invoice): void
    {
        $dirty = array_keys($invoice->getDirty());
        $meaningful = array_values(array_diff($dirty, self::IGNORE_ON_UPDATE));
        if ($meaningful === []) {
            return;
        }

        $invoice->loadMissing('lead');
        $original = $invoice->getOriginal();
        $oldSlice = array_intersect_key($original, array_flip($meaningful));
        $newSlice = $invoice->only($meaningful);

        LeadActionLogger::log(
            $invoice->lead,
            'invoice_updated',
            'Invoice '.($invoice->invoice_number ?? '#'.$invoice->id).' updated',
            $oldSlice,
            $newSlice
        );
    }

    public function deleted(Invoice $invoice): void
    {
        $invoice->loadMissing('lead');
        LeadActionLogger::log(
            $invoice->lead,
            'invoice_deleted',
            'Invoice '.($invoice->invoice_number ?? '#'.$invoice->id).' deleted',
            null,
            ['invoice_id' => $invoice->id]
        );
    }
}
