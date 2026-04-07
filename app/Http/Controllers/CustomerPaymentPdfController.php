<?php

namespace App\Http\Controllers;

use App\Models\CustomerPayment;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class CustomerPaymentPdfController
{
    public function __invoke(CustomerPayment $customerPayment): Response
    {
        $user = auth()->user();
        if (! $user) {
            abort(403);
        }

        $customerPayment->load([
            'invoice.lead',
            'invoice.lineItems' => fn ($q) => $q->orderBy('sort_order'),
        ]);

        $invoice = $customerPayment->invoice;
        if (! $invoice) {
            abort(404);
        }

        if (! $user->canViewInvoice($invoice)) {
            abort(403);
        }

        $rn = $customerPayment->receipt_number ?: 'receipt-'.$customerPayment->id;
        $filename = str_replace(['/', '\\'], '-', $rn).'.pdf';
        $company = config('ceysaid.company');
        $logoRelative = $company['logo_path'] ?? null;
        $logoPath = $logoRelative ? public_path($logoRelative) : null;
        $logoPath = $logoPath && is_file($logoPath) ? $logoPath : null;

        $pdf = Pdf::loadView('pdf.receipt', [
            'payment' => $customerPayment,
            'invoice' => $invoice,
            'company' => $company,
            'logoPath' => $logoPath,
        ]);

        return $pdf->download($filename);
    }
}
