<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Services\SupplierBankBookService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SupplierPayablePdfController
{
    public function __invoke(Supplier $supplier): Response
    {
        $user = auth()->user();
        if (! $user || (! $user->isAdmin() && ! $user->isAccount())) {
            abort(403);
        }

        $bankBookRows = app(SupplierBankBookService::class)->rows($supplier);

        $filename = 'Supplier-payable-'.Str::slug($supplier->name).'-'.$supplier->id.'.pdf';
        $company = config('ceysaid.company');
        $logoRelative = $company['logo_path'] ?? null;
        $logoPath = $logoRelative ? public_path($logoRelative) : null;
        $logoPath = $logoPath && is_file($logoPath) ? $logoPath : null;

        $pdf = Pdf::loadView('pdf.supplier-payable', [
            'supplier' => $supplier,
            'bankBookRows' => $bankBookRows,
            'company' => $company,
            'logoPath' => $logoPath,
        ]);

        return $pdf->download($filename);
    }
}
