<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PaymentRegisterService
{
    public function paginate(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        return $this->registerQuery($filters)->orderByDesc('payment_date')->orderByDesc('created_at')->orderByDesc('id')->paginate($perPage);
    }

    public function all(array $filters): Collection
    {
        return $this->registerQuery($filters)->orderByDesc('payment_date')->orderByDesc('created_at')->get();
    }

    public function summary(array $filters): array
    {
        $row = $this->registerQuery($filters)
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'in' THEN amount ELSE 0 END), 0) as received")
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'out' THEN amount ELSE 0 END), 0) as paid")
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'transfer' THEN amount ELSE 0 END), 0) as transfers")
            ->selectRaw('COUNT(*) as transaction_count')->first();
        $received = round((float) ($row->received ?? 0), 2);
        $paid = round((float) ($row->paid ?? 0), 2);

        return ['received' => $received, 'paid' => $paid, 'transfers' => round((float) ($row->transfers ?? 0), 2), 'net' => round($received - $paid, 2), 'count' => (int) ($row->transaction_count ?? 0)];
    }

    private function registerQuery(array $filters): Builder
    {
        $union = $this->customerPaymentsQuery()->unionAll($this->supplierPaymentsQuery())->unionAll($this->legacyVendorPaymentsQuery())->unionAll($this->expensesQuery())->unionAll($this->transfersQuery());

        return DB::query()->fromSub($union, 'payment_register')
            ->when($filters['date_from'] ?? null, fn (Builder $q, string $v) => $q->whereDate('payment_date', '>=', $v))
            ->when($filters['date_to'] ?? null, fn (Builder $q, string $v) => $q->whereDate('payment_date', '<=', $v))
            ->when($filters['direction'] ?? null, fn (Builder $q, string $v) => $q->where('direction', $v))
            ->when($filters['transaction_type'] ?? null, fn (Builder $q, string $v) => $q->where('transaction_type', $v))
            ->when($filters['payment_method'] ?? null, fn (Builder $q, string $v) => $q->where('payment_method', $v))
            ->when($filters['account'] ?? null, fn (Builder $q, string $v) => $q->where(fn (Builder $q) => $q->where('account_from', $v)->orWhere('account_to', $v)))
            ->when($filters['search'] ?? null, function (Builder $q, string $search): void {
                $like = '%'.trim($search).'%';
                $q->where(function (Builder $q) use ($like): void {
                    foreach (['reference', 'invoice_number', 'vendor_bill_number', 'lead_reference', 'party', 'supplier', 'sales_person', 'category', 'created_by_name'] as $column) {
                        $q->orWhere($column, 'like', $like);
                    }
                });
            });
    }

    private function customerPaymentsQuery(): Builder
    {
        return DB::table('customer_payments as p')->join('invoices as i', 'i.id', '=', 'p.invoice_id')->join('leads as l', 'l.id', '=', 'i.lead_id')->leftJoin('users as sales', 'sales.id', '=', 'i.sales_person_id')->select([
            'p.id', DB::raw("'customer_receipt' as transaction_type"), DB::raw("'in' as direction"), DB::raw('NULL as supplier_payment_id'), DB::raw('NULL as internal_transfer_id'), 'p.payment_date', 'p.receipt_number as reference', 'i.id as invoice_id', 'i.invoice_number', DB::raw('NULL as vendor_bill_number'), 'l.id as lead_id', 'l.reference_id as lead_reference', 'l.customer_name as party', DB::raw('NULL as supplier'), 'sales.name as sales_person', DB::raw('NULL as category'), 'p.payment_method', 'p.deposit_to as account_from', 'p.deposit_to as account_to', 'p.amount', DB::raw('NULL as created_by_name'), 'p.created_at',
        ]);
    }

    private function supplierPaymentsQuery(): Builder
    {
        return DB::table('supplier_payments as p')->leftJoin('suppliers as s', 's.id', '=', 'p.supplier_id')->leftJoin('users as u', 'u.id', '=', 'p.created_by')->select([
            'p.id', DB::raw("'supplier_payment' as transaction_type"), DB::raw("'out' as direction"), 'p.id as supplier_payment_id', DB::raw('NULL as internal_transfer_id'), 'p.payment_date', 'p.payment_number as reference',
            DB::raw('(SELECT MIN(i.id) FROM vendor_bill_payments vbp JOIN vendor_bills vb ON vb.id = vbp.vendor_bill_id LEFT JOIN invoices i ON i.id = vb.invoice_id WHERE vbp.supplier_payment_id = p.id) as invoice_id'),
            DB::raw('(SELECT GROUP_CONCAT(DISTINCT i.invoice_number) FROM vendor_bill_payments vbp JOIN vendor_bills vb ON vb.id = vbp.vendor_bill_id LEFT JOIN invoices i ON i.id = vb.invoice_id WHERE vbp.supplier_payment_id = p.id) as invoice_number'),
            DB::raw('(SELECT GROUP_CONCAT(DISTINCT vb.vendor_bill_number) FROM vendor_bill_payments vbp JOIN vendor_bills vb ON vb.id = vbp.vendor_bill_id WHERE vbp.supplier_payment_id = p.id) as vendor_bill_number'),
            DB::raw('(SELECT MIN(l.id) FROM vendor_bill_payments vbp JOIN vendor_bills vb ON vb.id = vbp.vendor_bill_id LEFT JOIN invoices i ON i.id = vb.invoice_id LEFT JOIN leads l ON l.id = i.lead_id WHERE vbp.supplier_payment_id = p.id) as lead_id'),
            DB::raw('(SELECT GROUP_CONCAT(DISTINCT l.reference_id) FROM vendor_bill_payments vbp JOIN vendor_bills vb ON vb.id = vbp.vendor_bill_id LEFT JOIN invoices i ON i.id = vb.invoice_id LEFT JOIN leads l ON l.id = i.lead_id WHERE vbp.supplier_payment_id = p.id) as lead_reference'),
            DB::raw("COALESCE(s.name, 'Supplier payment') as party"), 's.name as supplier',
            DB::raw('(SELECT GROUP_CONCAT(DISTINCT sales.name) FROM vendor_bill_payments vbp JOIN vendor_bills vb ON vb.id = vbp.vendor_bill_id LEFT JOIN invoices i ON i.id = vb.invoice_id LEFT JOIN users sales ON sales.id = i.sales_person_id WHERE vbp.supplier_payment_id = p.id) as sales_person'),
            DB::raw('NULL as category'), 'p.payment_mode as payment_method', 'p.paid_through as account_from', 'p.paid_through as account_to', 'p.amount', 'u.name as created_by_name', 'p.created_at',
        ]);
    }

    private function legacyVendorPaymentsQuery(): Builder
    {
        return DB::table('vendor_bill_payments as p')->join('vendor_bills as b', 'b.id', '=', 'p.vendor_bill_id')->leftJoin('invoices as i', 'i.id', '=', 'b.invoice_id')->leftJoin('leads as l', 'l.id', '=', 'i.lead_id')->leftJoin('suppliers as s', 's.id', '=', 'b.supplier_id')->leftJoin('users as sales', 'sales.id', '=', 'i.sales_person_id')->whereNull('p.supplier_payment_id')->select([
            'p.id', DB::raw("'vendor_payment' as transaction_type"), DB::raw("'out' as direction"), DB::raw('NULL as supplier_payment_id'), DB::raw('NULL as internal_transfer_id'), 'p.payment_date', 'b.vendor_bill_number as reference', 'i.id as invoice_id', 'i.invoice_number', 'b.vendor_bill_number', 'l.id as lead_id', 'l.reference_id as lead_reference', DB::raw('COALESCE(s.name, b.vendor_name) as party'), DB::raw('COALESCE(s.name, b.vendor_name) as supplier'), 'sales.name as sales_person', DB::raw('NULL as category'), 'p.payment_mode as payment_method', 'p.paid_through as account_from', 'p.paid_through as account_to', 'p.amount', DB::raw('NULL as created_by_name'), 'p.created_at',
        ]);
    }

    private function expensesQuery(): Builder
    {
        return DB::table('expenses as p')->leftJoin('expense_categories as c', 'c.id', '=', 'p.category_id')->leftJoin('users as u', 'u.id', '=', 'p.created_by')->select([
            'p.id', DB::raw("'expense' as transaction_type"), DB::raw("'out' as direction"), DB::raw('NULL as supplier_payment_id'), DB::raw('NULL as internal_transfer_id'), 'p.expense_date as payment_date', 'p.reference_number as reference', DB::raw('NULL as invoice_id'), DB::raw('NULL as invoice_number'), DB::raw('NULL as vendor_bill_number'), DB::raw('NULL as lead_id'), DB::raw('NULL as lead_reference'), 'p.description as party', DB::raw('NULL as supplier'), DB::raw('NULL as sales_person'), DB::raw('COALESCE(c.name, p.category) as category'), 'p.payment_mode as payment_method', 'p.paid_through as account_from', 'p.paid_through as account_to', 'p.amount', 'u.name as created_by_name', 'p.created_at',
        ]);
    }

    private function transfersQuery(): Builder
    {
        return DB::table('internal_transfers as p')->leftJoin('users as u', 'u.id', '=', 'p.created_by')->select([
            'p.id', DB::raw("'internal_transfer' as transaction_type"), DB::raw("'transfer' as direction"), DB::raw('NULL as supplier_payment_id'), 'p.id as internal_transfer_id', 'p.transfer_date as payment_date', 'p.transfer_number as reference', DB::raw('NULL as invoice_id'), DB::raw('NULL as invoice_number'), DB::raw('NULL as vendor_bill_number'), DB::raw('NULL as lead_id'), DB::raw('NULL as lead_reference'), DB::raw("'Internal transfer' as party"), DB::raw('NULL as supplier'), DB::raw('NULL as sales_person'), DB::raw('NULL as category'), DB::raw("'internal_transfer' as payment_method"), 'p.from_account as account_from', 'p.to_account as account_to', 'p.amount', 'u.name as created_by_name', 'p.created_at',
        ]);
    }
}
