<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SupplierPayablesSummaryPdfController
{
    public function __invoke(): Response
    {
        $user = auth()->user();
        if (! $user || (! $user->isAdmin() && ! $user->isAccount())) {
            abort(403);
        }

        $suppliers = Supplier::query()
            ->with(['vendorBills.vendorBillPayments'])
            ->withCount('vendorBills')
            ->select('suppliers.*')
            ->selectSub(
                DB::table('vendor_bills as vb')
                    ->selectRaw('COALESCE(SUM(CASE WHEN (vb.bill_amount - COALESCE((SELECT SUM(vbp.amount) FROM vendor_bill_payments vbp WHERE vbp.vendor_bill_id = vb.id), 0)) < 0 THEN 0 ELSE (vb.bill_amount - COALESCE((SELECT SUM(vbp.amount) FROM vendor_bill_payments vbp WHERE vbp.vendor_bill_id = vb.id), 0)) END), 0)')
                    ->whereColumn('vb.supplier_id', 'suppliers.id'),
                'payable_amount',
            )
            ->orderByDesc('payable_amount')
            ->orderBy('suppliers.name')
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
