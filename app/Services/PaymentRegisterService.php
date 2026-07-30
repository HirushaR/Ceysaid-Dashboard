<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class PaymentRegisterService
{
    /**
     * @param  array{
     *     date_from?: string|null,
     *     date_to?: string|null,
     *     direction?: string|null,
     *     payment_method?: string|null,
     *     account?: string|null,
     * }  $filters
     */
    public function paginate(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        return $this->registerQuery($filters)
            ->orderByDesc('payment_date')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{received: float, paid: float, net: float, count: int}
     */
    public function summary(array $filters): array
    {
        $row = $this->registerQuery($filters)
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'in' THEN amount ELSE 0 END), 0) as received")
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'out' THEN amount ELSE 0 END), 0) as paid")
            ->selectRaw('COUNT(*) as transaction_count')
            ->first();

        $received = round((float) ($row->received ?? 0), 2);
        $paid = round((float) ($row->paid ?? 0), 2);

        return [
            'received' => $received,
            'paid' => $paid,
            'net' => round($received - $paid, 2),
            'count' => (int) ($row->transaction_count ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function registerQuery(array $filters): Builder
    {
        $direction = $filters['direction'] ?? null;

        if ($direction === 'in') {
            return DB::query()->fromSub($this->customerPaymentsQuery($filters), 'payment_register');
        }

        if ($direction === 'out') {
            return DB::query()->fromSub($this->vendorPaymentsQuery($filters), 'payment_register');
        }

        $union = $this->customerPaymentsQuery($filters)
            ->unionAll($this->vendorPaymentsQuery($filters));

        return DB::query()->fromSub($union, 'payment_register');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function customerPaymentsQuery(array $filters): Builder
    {
        return DB::table('customer_payments as payment')
            ->join('invoices as invoice', 'invoice.id', '=', 'payment.invoice_id')
            ->join('leads as lead', 'lead.id', '=', 'invoice.lead_id')
            ->select([
                'payment.id',
                DB::raw("'in' as direction"),
                'payment.payment_date',
                'payment.receipt_number as reference',
                'invoice.id as invoice_id',
                'invoice.invoice_number',
                'lead.id as lead_id',
                'lead.reference_id as lead_reference',
                'lead.customer_name as party',
                DB::raw('NULL as supplier'),
                'payment.payment_method as payment_method',
                'payment.deposit_to as account',
                'payment.amount',
                'payment.created_at',
            ])
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('payment.payment_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('payment.payment_date', '<=', $date))
            ->when($filters['payment_method'] ?? null, fn (Builder $query, string $method): Builder => $query->where('payment.payment_method', $method))
            ->when($filters['account'] ?? null, fn (Builder $query, string $account): Builder => $query->where('payment.deposit_to', $account));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function vendorPaymentsQuery(array $filters): Builder
    {
        return DB::table('vendor_bill_payments as payment')
            ->join('vendor_bills as bill', 'bill.id', '=', 'payment.vendor_bill_id')
            ->join('invoices as invoice', 'invoice.id', '=', 'bill.invoice_id')
            ->join('leads as lead', 'lead.id', '=', 'invoice.lead_id')
            ->leftJoin('suppliers as supplier', 'supplier.id', '=', 'bill.supplier_id')
            ->select([
                'payment.id',
                DB::raw("'out' as direction"),
                'payment.payment_date',
                'bill.vendor_bill_number as reference',
                'invoice.id as invoice_id',
                'invoice.invoice_number',
                'lead.id as lead_id',
                'lead.reference_id as lead_reference',
                DB::raw('COALESCE(supplier.name, bill.vendor_name) as party'),
                DB::raw('COALESCE(supplier.name, bill.vendor_name) as supplier'),
                'payment.payment_mode as payment_method',
                'payment.paid_through as account',
                'payment.amount',
                'payment.created_at',
            ])
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('payment.payment_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('payment.payment_date', '<=', $date))
            ->when($filters['payment_method'] ?? null, fn (Builder $query, string $method): Builder => $query->where('payment.payment_mode', $method))
            ->when($filters['account'] ?? null, fn (Builder $query, string $account): Builder => $query->where('payment.paid_through', $account));
    }
}
