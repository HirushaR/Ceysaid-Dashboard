<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class QuotePdfController
{
    public function __invoke(Quote $quote): Response
    {
        $user = auth()->user();
        if (! $user || (! $user->hasPermission('quotes.view') && ! $user->isAccount() && ! $user->isAdmin())) {
            abort(403);
        }

        $quote->load([
            'lead',
            'lineItems' => fn ($q) => $q->orderBy('sort_order'),
        ]);

        $filename = str_replace(['/', '\\'], '-', $quote->quote_number).'.pdf';
        $pdf = Pdf::loadView('pdf.quote', ['quote' => $quote, 'company' => config('ceysaid.company')]);

        return $pdf->download($filename);
    }
}
