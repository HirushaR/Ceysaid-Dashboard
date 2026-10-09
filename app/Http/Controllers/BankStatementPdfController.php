<?php

namespace App\Http\Controllers;

use App\Services\BankStatementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BankStatementPdfController
{
    public function __invoke(Request $request, BankStatementService $service): Response
    {
        abort_unless(auth()->user()->canManageAccountingRecords(), 403);
        $data = $request->validate(['account' => ['required', 'exists:financial_accounts,code'], 'date_from' => ['required', 'date'], 'date_to' => ['required', 'date', 'after_or_equal:date_from']]);
        $statement = $service->statement($data['account'], $data['date_from'], $data['date_to']);

        return Pdf::loadView('pdf.bank-statement', ['statement' => $statement, 'from' => $data['date_from'], 'to' => $data['date_to'], 'company' => config('ceysaid.company')])->download('bank-statement-'.$data['account'].'-'.$data['date_from'].'-'.$data['date_to'].'.pdf');
    }
}
