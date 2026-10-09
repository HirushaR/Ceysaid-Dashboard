<?php

namespace App\Services;

use App\Models\FinancialAccount;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BankStatementService
{
    /** @return array{account: FinancialAccount, opening: float, credits: float, debits: float, closing: float, rows: Collection<int, object>} */
    public function statement(string $accountCode, string $from, string $to): array
    {
        $account = FinancialAccount::query()->where('code', $accountCode)->firstOrFail();
        $fromDate = Carbon::parse($from)->startOfDay();
        $toDate = Carbon::parse($to)->endOfDay();
        $allRows = $this->ledgerRows($accountCode);

        $openingDate = $account->opening_balance_date?->copy()->startOfDay();
        $opening = ! $openingDate || $openingDate->lte($fromDate) ? (float) $account->opening_balance : 0.0;
        $opening += $allRows
            ->filter(function (object $row) use ($fromDate, $openingDate): bool {
                $date = Carbon::parse($row->transaction_date)->startOfDay();

                return $date->lt($fromDate) && (! $openingDate || ($openingDate->lt($fromDate) && $date->gte($openingDate)));
            })
            ->sum(fn (object $row): float => (float) $row->credit - (float) $row->debit);

        $running = $opening;
        $rows = $allRows
            ->filter(fn (object $row): bool => Carbon::parse($row->transaction_date)->betweenIncluded($fromDate, $toDate))
            ->values()
            ->map(function (object $row) use (&$running): object {
                $running += (float) $row->credit - (float) $row->debit;
                $row->balance = round($running, 2);

                return $row;
            });

        $credits = (float) $rows->sum('credit');
        $debits = (float) $rows->sum('debit');

        return compact('account', 'opening', 'credits', 'debits', 'running', 'rows') + ['closing' => round($opening + $credits - $debits, 2)];
    }

    /** @return Collection<int, object> */
    private function ledgerRows(string $account): Collection
    {
        $receipts = DB::table('customer_payments as p')
            ->join('invoices as i', 'i.id', '=', 'p.invoice_id')
            ->leftJoin('leads as l', 'l.id', '=', 'i.lead_id')
            ->where('p.deposit_to', $account)
            ->selectRaw("p.id, p.payment_date as transaction_date, p.created_at, 'Customer receipt' as type, p.receipt_number as reference, COALESCE(l.customer_name, i.invoice_number) as description, p.amount as credit, 0 as debit")
            ->get();

        $supplierPayments = DB::table('supplier_payments as p')
            ->leftJoin('suppliers as s', 's.id', '=', 'p.supplier_id')
            ->where('p.paid_through', $account)
            ->selectRaw("p.id, p.payment_date as transaction_date, p.created_at, 'Supplier payment' as type, p.payment_number as reference, COALESCE(s.name, 'Supplier payment') as description, 0 as credit, p.amount as debit")
            ->get();

        $legacyPayments = DB::table('vendor_bill_payments as p')
            ->join('vendor_bills as b', 'b.id', '=', 'p.vendor_bill_id')
            ->whereNull('p.supplier_payment_id')->where('p.paid_through', $account)
            ->selectRaw("p.id, p.payment_date as transaction_date, p.created_at, 'Vendor payment' as type, b.vendor_bill_number as reference, COALESCE(b.vendor_name, b.vendor_bill_number) as description, 0 as credit, p.amount as debit")
            ->get();

        $expenses = DB::table('expenses as e')
            ->where('e.paid_through', $account)
            ->selectRaw("e.id, e.expense_date as transaction_date, e.created_at, 'Expense' as type, e.reference_number as reference, e.description, 0 as credit, e.amount as debit")
            ->get();

        $transfersOut = DB::table('internal_transfers as t')
            ->where('t.from_account', $account)
            ->selectRaw("t.id, t.transfer_date as transaction_date, t.created_at, 'Transfer out' as type, t.transfer_number as reference, t.to_account as description, 0 as credit, t.amount as debit")
            ->get();

        $transfersIn = DB::table('internal_transfers as t')
            ->where('t.to_account', $account)
            ->selectRaw("t.id, t.transfer_date as transaction_date, t.created_at, 'Transfer in' as type, t.transfer_number as reference, t.from_account as description, t.amount as credit, 0 as debit")
            ->get();

        return collect()->concat($receipts)->concat($supplierPayments)->concat($legacyPayments)->concat($expenses)->concat($transfersOut)->concat($transfersIn)
            ->sortBy(fn (object $row): string => $row->transaction_date.'|'.$row->created_at.'|'.str_pad((string) $row->id, 10, '0', STR_PAD_LEFT))
            ->values();
    }
}
