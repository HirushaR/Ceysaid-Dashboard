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

        $vendorBill->load([
            'invoice.lead',
            'supplier',
            'lineItems' => fn ($q) => $q->orderBy('sort_order'),
            'vendorBillPayments' => fn ($q) => $q->orderByDesc('payment_date')->orderByDesc('id'),
        ]);

        if (! $vendorBill->invoice || ! $user->canViewInvoice($vendorBill->invoice)) {
            abort(403);
        }

        $filename = str_replace(['/', '\\'], '-', $vendorBill->vendor_bill_number).'.pdf';
        $company = config('ceysaid.company');
        $logoRelative = $company['logo_path'] ?? null;
        $logoPath = $logoRelative ? public_path($logoRelative) : null;
        $logoPath = $logoPath && is_file($logoPath) ? $logoPath : null;

        $pdf = Pdf::loadView('pdf.vendor-bill', [
            'vendorBill' => $vendorBill,
            'company' => $company,
            'logoPath' => $logoPath,
        ]);

        return $pdf->download($filename);
    }
}
