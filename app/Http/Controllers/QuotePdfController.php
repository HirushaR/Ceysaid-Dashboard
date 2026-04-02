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
        $company = config('ceysaid.company');
        $logoRelative = $company['logo_path'] ?? null;
        $logoPath = $logoRelative ? public_path($logoRelative) : null;
        $logoPath = $logoPath && is_file($logoPath) ? $logoPath : null;

        $pdf = Pdf::loadView('pdf.quote', [
            'quote' => $quote,
            'company' => $company,
            'logoPath' => $logoPath,
        ]);

        return $pdf->download($filename);
    }
}
