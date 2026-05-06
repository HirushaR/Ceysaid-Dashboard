<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
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

        $openBills = $supplier->vendorBills()
            ->whereIn('payment_status', ['pending', 'partial'])
            ->with(['invoice.lead', 'vendorBillPayments'])
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_date')
            ->get();

        $payments = $supplier->vendorBillPayments()
            ->with(['vendorBill.invoice.lead'])
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->get();

        $filename = 'Supplier-payable-'.Str::slug($supplier->name).'-'.$supplier->id.'.pdf';
        $company = config('ceysaid.company');
        $logoRelative = $company['logo_path'] ?? null;
        $logoPath = $logoRelative ? public_path($logoRelative) : null;
        $logoPath = $logoPath && is_file($logoPath) ? $logoPath : null;

        $pdf = Pdf::loadView('pdf.supplier-payable', [
            'supplier' => $supplier,
            'openBills' => $openBills,
            'payments' => $payments,
            'company' => $company,
            'logoPath' => $logoPath,
        ]);

        return $pdf->download($filename);
    }
}
