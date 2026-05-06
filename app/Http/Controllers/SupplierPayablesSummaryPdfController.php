<?php

namespace App\Http\Controllers;

use App\Filament\Resources\SupplierPayablesResource;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class SupplierPayablesSummaryPdfController
{
    public function __invoke(): Response
    {
        $user = auth()->user();
        if (! $user || (! $user->isAdmin() && ! $user->isAccount())) {
            abort(403);
        }

        $suppliers = SupplierPayablesResource::getEloquentQuery()
            ->withCount('vendorBills')
            ->get();

        $filename = 'Supplier-payables-summary-'.now()->format('Y-m-d').'.pdf';
        $company = config('ceysaid.company');
        $logoRelative = $company['logo_path'] ?? null;
        $logoPath = $logoRelative ? public_path($logoRelative) : null;
        $logoPath = $logoPath && is_file($logoPath) ? $logoPath : null;

        $pdf = Pdf::loadView('pdf.supplier-payables-summary', [
            'suppliers' => $suppliers,
            'company' => $company,
            'logoPath' => $logoPath,
        ]);

        return $pdf->download($filename);
    }
}
