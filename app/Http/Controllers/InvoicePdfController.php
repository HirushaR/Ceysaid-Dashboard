<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class InvoicePdfController
{
    public function __invoke(Invoice $invoice): Response
    {
        $user = auth()->user();
        if (! $user || ! $user->canViewInvoice($invoice)) {
            abort(403);
        }

        $invoice->load([
            'lead.assignedUser',
            'salesPerson',
            'lineItems' => fn ($q) => $q->orderBy('sort_order'),
        ]);

        $filename = str_replace(['/', '\\'], '-', $invoice->invoice_number).'.pdf';
        $company = config('ceysaid.company');
        $company['bank'] = $company['bank_accounts'][$invoice->bank_account] ?? $company['bank'];
        $logoRelative = $company['logo_path'] ?? null;
        $logoPath = $logoRelative ? public_path($logoRelative) : null;
        $logoPath = $logoPath && is_file($logoPath) ? $logoPath : null;

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'company' => $company,
            'logoPath' => $logoPath,
        ]);

        return $pdf->download($filename);
    }
}
