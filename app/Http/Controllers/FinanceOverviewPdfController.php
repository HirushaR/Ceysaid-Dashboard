<?php

namespace App\Http\Controllers;

use App\Services\FinanceCashFlowService;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class FinanceOverviewPdfController
{
    public function __invoke(FinanceCashFlowService $cashFlow): Response
    {
        abort_unless(auth()->user()->canManageAccountingRecords(), 403);
        $asOf = today();

        return Pdf::loadView('pdf.finance-overview', [
            'company' => config('ceysaid.company'),
            'asOf' => $asOf,
            'forecast' => $cashFlow->forecast($asOf),
            'overdue' => $cashFlow->overdue($asOf),
            'receivables' => $cashFlow->nextReceivables($asOf, 100),
            'payables' => $cashFlow->nextPayables($asOf, 100),
        ])->setPaper('a4', 'landscape')->download('finance-overview-'.$asOf->toDateString().'.pdf');
    }
}
