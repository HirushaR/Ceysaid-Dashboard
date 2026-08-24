<?php

namespace App\Observers;

use App\Models\VendorBill;
use App\Services\LeadActionLogger;

class VendorBillObserver
{
    /** @var list<string> */
    private const IGNORE_ON_UPDATE = [
        'updated_at',
    ];

    public function saving(VendorBill $vendorBill): void
    {
        $vendorBill->loadMissing('invoice.lead');
        $lead = $vendorBill->invoice?->lead;

        if ($lead?->is_group_lead && ! $lead->tour_id) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'invoice_id' => 'Cannot add vendor bills for group leads without a linked tour.',
            ]);
        }
    }

    public function created(VendorBill $vendorBill): void
    {
        $vendorBill->loadMissing('invoice.lead', 'invoice');
        $inv = $vendorBill->invoice;
        $num = $vendorBill->vendor_bill_number ?? '#'.$vendorBill->id;
        $invLabel = $inv ? $inv->invoice_number : '—';

        LeadActionLogger::log(
            $inv?->lead,
            'vendor_bill_created',
            "Vendor bill {$num} ({$vendorBill->vendor_name}) for invoice {$invLabel} — LKR ".number_format((float) $vendorBill->bill_amount, 2),
            null,
            [
                'vendor_bill_id' => $vendorBill->id,
                'vendor_bill_number' => $vendorBill->vendor_bill_number,
                'invoice_id' => $vendorBill->invoice_id,
            ]
        );
    }

    public function updated(VendorBill $vendorBill): void
    {
        $dirty = array_keys($vendorBill->getDirty());
        $meaningful = array_values(array_diff($dirty, self::IGNORE_ON_UPDATE));
        if ($meaningful === []) {
            return;
        }

        $vendorBill->loadMissing('invoice.lead', 'invoice');
        $original = $vendorBill->getOriginal();
        $oldSlice = array_intersect_key($original, array_flip($meaningful));
        $newSlice = $vendorBill->only($meaningful);

        $inv = $vendorBill->invoice;
        $num = $vendorBill->vendor_bill_number ?? '#'.$vendorBill->id;

        LeadActionLogger::log(
            $inv?->lead,
            'vendor_bill_updated',
            "Vendor bill {$num} updated",
            $oldSlice,
            $newSlice
        );
    }

    public function deleted(VendorBill $vendorBill): void
    {
        $vendorBill->loadMissing('invoice.lead', 'invoice');
        $inv = $vendorBill->invoice;
        $num = $vendorBill->vendor_bill_number ?? '#'.$vendorBill->id;

        LeadActionLogger::log(
            $inv?->lead,
            'vendor_bill_deleted',
            "Vendor bill {$num} ({$vendorBill->vendor_name}) deleted",
            null,
            ['vendor_bill_id' => $vendorBill->id, 'invoice_id' => $vendorBill->invoice_id]
        );
    }
}
