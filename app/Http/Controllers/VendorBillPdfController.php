<?php

namespace App\Http\Controllers;

use App\Models\VendorBill;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class VendorBillPdfController
{
    public function __invoke(VendorBill $vendorBill): Response
    {
        $user = auth()->user();
        if (! $user || (! $user->hasPermission('vendor_bills.view') && ! $user->isAccount() && ! $user->isAdmin())) {
            abort(403);
        }

        $vendorBill->load(['invoice.lead', 'supplier']);

        $filename = str_replace(['/', '\\'], '-', $vendorBill->vendor_bill_number).'.pdf';
        $pdf = Pdf::loadView('pdf.vendor-bill', [
            'vendorBill' => $vendorBill,
            'company' => config('ceysaid.company'),
        ]);

        return $pdf->download($filename);
    }
}
