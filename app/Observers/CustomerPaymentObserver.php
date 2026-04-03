<?php

namespace App\Observers;

use App\Models\CustomerPayment;
use App\Services\LeadActionLogger;

class CustomerPaymentObserver
{
    /** @var list<string> */
    private const IGNORE_ON_UPDATE = [
        'updated_at',
    ];

    public function created(CustomerPayment $payment): void
    {
        $payment->loadMissing('invoice.lead', 'invoice');
        $inv = $payment->invoice;
        $invLabel = $inv ? $inv->invoice_number : '—';
        $rc = $payment->receipt_number ? " ({$payment->receipt_number})" : '';

        LeadActionLogger::log(
            $inv?->lead,
            'payment_created',
            'Customer payment'.$rc.' LKR '.number_format((float) $payment->amount, 2).' recorded for invoice '.$invLabel,
            null,
            [
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'invoice_id' => $payment->invoice_id,
            ]
        );
    }

    public function updated(CustomerPayment $payment): void
    {
        $dirty = array_keys($payment->getDirty());
        $meaningful = array_values(array_diff($dirty, self::IGNORE_ON_UPDATE));
        if ($meaningful === []) {
            return;
        }

        $payment->loadMissing('invoice.lead', 'invoice');
        $original = $payment->getOriginal();
        $oldSlice = array_intersect_key($original, array_flip($meaningful));
        $newSlice = $payment->only($meaningful);

        LeadActionLogger::log(
            $payment->invoice?->lead,
            'payment_updated',
            'Customer payment #'.$payment->id.' updated',
            $oldSlice,
            $newSlice
        );
    }

    public function deleted(CustomerPayment $payment): void
    {
        $payment->loadMissing('invoice.lead', 'invoice');
        $inv = $payment->invoice;
        $invLabel = $inv ? $inv->invoice_number : '—';

        LeadActionLogger::log(
            $inv?->lead,
            'payment_deleted',
            'Customer payment LKR '.number_format((float) $payment->amount, 2).' removed (invoice '.$invLabel.')',
            null,
            ['payment_id' => $payment->id, 'invoice_id' => $payment->invoice_id]
        );
    }
}
