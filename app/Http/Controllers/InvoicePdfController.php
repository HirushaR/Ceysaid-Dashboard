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
        if (! $user || (! $user->hasPermission('invoices.view') && ! $user->isAccount() && ! $user->isAdmin())) {
            abort(403);
        }

        $invoice->load([
            'lead',
            'lineItems' => fn ($q) => $q->orderBy('sort_order'),
        ]);

        $filename = str_replace(['/', '\\'], '-', $invoice->invoice_number).'.pdf';
        $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $invoice, 'company' => config('ceysaid.company')]);

        return $pdf->download($filename);
    }
}
