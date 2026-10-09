<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\SupplierPayment;
use App\Services\AuditService;
use App\Services\BankStatementService;
use App\Services\PaymentRegisterService;
use App\Services\SimpleXlsxWriter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FinanceExportController
{
    public function __invoke(string $type, Request $request, SimpleXlsxWriter $xlsx, PaymentRegisterService $payments, BankStatementService $statements): BinaryFileResponse
    {
        abort_unless(auth()->user()->canManageAccountingRecords(), 403);
        [$title, $rows] = match ($type) {
            'payment-register' => $this->paymentRegister($request, $payments),
            'bank-statement' => $this->bankStatement($request, $statements),
            'expenses' => $this->expenses(),
            'vendor-payments' => $this->vendorPayments(),
            'invoices' => $this->invoices(),
            default => abort(404),
        };
        $path = $xlsx->write($title, $rows);
        app(AuditService::class)->record('finance.exported', null, [], ['type' => $type, 'filters' => $request->query()]);

        return response()->download($path, str($title)->slug('-').'-'.now()->format('Ymd-His').'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
    }

    private function paymentRegister(Request $request, PaymentRegisterService $service): array
    {
        $filters = $request->only(['search', 'direction', 'transaction_type', 'payment_method', 'account', 'date_from', 'date_to']);
        $rows = [['Date', 'Type', 'Direction', 'Reference', 'Invoice', 'Vendor Bill', 'Lead', 'Party', 'Supplier', 'Sales Person', 'Method', 'From Account', 'To Account', 'Recorded By', 'Amount']];
        foreach ($service->all($filters) as $row) {
            $rows[] = [$row->payment_date, str_replace('_', ' ', $row->transaction_type), $row->direction, $row->reference, $row->invoice_number, $row->vendor_bill_number, $row->lead_reference, $row->party, $row->supplier, $row->sales_person, $row->payment_method, $row->account_from, $row->account_to, $row->created_by_name, (float) $row->amount];
        }

        return ['Payment Register', $rows];
    }

    private function bankStatement(Request $request, BankStatementService $service): array
    {
        $data = $request->validate(['account' => ['required', 'exists:financial_accounts,code'], 'date_from' => ['required', 'date'], 'date_to' => ['required', 'date', 'after_or_equal:date_from']]);
        $statement = $service->statement($data['account'], $data['date_from'], $data['date_to']);
        $rows = [['Date', 'Type', 'Reference', 'Description', 'Debit', 'Credit', 'Balance'], [$data['date_from'], 'Opening balance', '', '', 0, 0, $statement['opening']]];
        foreach ($statement['rows'] as $row) {
            $rows[] = [$row->transaction_date, $row->type, $row->reference, $row->description, (float) $row->debit, (float) $row->credit, (float) $row->balance];
        }

        return ['Bank Statement '.$statement['account']->name, $rows];
    }

    private function expenses(): array
    {
        $rows = [['Date', 'Category', 'Description', 'Method', 'Paid Through', 'Reference', 'Recorded By', 'Amount']];
        Expense::with(['expenseCategory', 'creator'])->latest('expense_date')->get()->each(function ($row) use (&$rows): void {
            $rows[] = [$row->expense_date->toDateString(), $row->expenseCategory?->name ?? $row->category, $row->description, $row->payment_mode, $row->paid_through, $row->reference_number, $row->creator?->name, (float) $row->amount];
        });

        return ['Expenses', $rows];
    }

    private function vendorPayments(): array
    {
        $rows = [['Date', 'Payment Number', 'Supplier', 'Method', 'Paid Through', 'Reference', 'Vendor Bills', 'Invoices', 'Recorded By', 'Amount']];
        SupplierPayment::with(['supplier', 'creator', 'allocations.vendorBill.invoice'])->latest('payment_date')->get()->each(function ($row) use (&$rows): void {
            $rows[] = [$row->payment_date->toDateString(), $row->payment_number, $row->supplier?->name, $row->payment_mode, $row->paid_through, $row->reference_number, $row->allocations->pluck('vendorBill.vendor_bill_number')->filter()->join(', '), $row->allocations->pluck('vendorBill.invoice.invoice_number')->filter()->unique()->join(', '), $row->creator?->name, (float) $row->amount];
        });

        return ['Vendor Payments', $rows];
    }

    private function invoices(): array
    {
        $rows = [['Invoice', 'Date', 'Due Date', 'Customer', 'Lead', 'Sales Person', 'Status', 'Total', 'Received', 'Balance']];
        Invoice::with(['lead', 'salesPerson'])->latest('invoice_date')->get()->each(function ($row) use (&$rows): void {
            $rows[] = [$row->invoice_number, $row->invoice_date?->toDateString(), $row->due_date?->toDateString(), $row->lead?->customer_name, $row->lead?->reference_id, $row->salesPerson?->name, $row->customer_payment_status, (float) $row->total_amount, (float) $row->payment_amount, (float) $row->balance_amount];
        });

        return ['Invoices', $rows];
    }
}
