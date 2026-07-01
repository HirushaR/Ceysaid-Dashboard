<?php

namespace App\Services;

use App\Enums\TourStatus;
use App\Models\Invoice;
use App\Models\Tour;
use App\Models\VendorBill;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TourFinanceReportService
{
    /**
     * @param  array{
     *     tour_id?: int|null,
     *     sales_user_id?: int|null,
     *     departure_from?: string|null,
     *     departure_to?: string|null,
     *     tour_status?: string|null,
     * }  $filters
     */
    public function portfolioKpis(array $filters = []): array
    {
        $receivableQuery = $this->applyInvoiceFilters(
            Invoice::query()
                ->whereIn('customer_payment_status', ['pending', 'partial']),
            $filters
        );

        $totalReceivable = (float) (clone $receivableQuery)->sum('balance_amount');

        $overdueReceivable = (float) (clone $receivableQuery)
            ->where('due_date', '<', now()->toDateString())
            ->where('balance_amount', '>', 0)
            ->sum('balance_amount');

        $payableQuery = $this->applyVendorBillFilters(
            VendorBill::query()->whereIn('payment_status', ['pending', 'partial']),
            $filters
        );

        $totalPayable = $this->sumVendorOutstanding($payableQuery);

        $openTourProfit = $this->tourWiseProfit(array_merge($filters, [
            'tour_status' => TourStatus::Open->value,
        ]))->sum('gross_profit');

        return [
            'total_receivable' => round($totalReceivable, 2),
            'total_payable' => round($totalPayable, 2),
            'net_cash_gap' => round($totalReceivable - $totalPayable, 2),
            'overdue_receivable' => round($overdueReceivable, 2),
            'expected_gross_profit' => round($openTourProfit, 2),
        ];
    }

    /**
     * @return Collection<int, array{month: string, month_key: string, amount: float}>
     */
    public function monthlyReceivables(array $filters = []): Collection
    {
        $query = $this->applyInvoiceFilters(
            Invoice::query()
                ->whereIn('customer_payment_status', ['pending', 'partial'])
                ->whereNotNull('due_date')
                ->where('balance_amount', '>', 0),
            $filters
        );

        $monthExpr = $this->monthKeySql('due_date');

        return $query
            ->selectRaw("{$monthExpr} as month_key")
            ->selectRaw('SUM(balance_amount) as amount')
            ->groupBy('month_key')
            ->orderBy('month_key')
            ->get()
            ->map(fn ($row) => [
                'month_key' => $row->month_key,
                'month' => $this->formatMonthKey((string) $row->month_key),
                'amount' => round((float) $row->amount, 2),
            ]);
    }

    /**
     * @return Collection<int, array{month: string, month_key: string, amount: float}>
     */
    public function monthlyPayables(array $filters = []): Collection
    {
        $query = $this->applyVendorBillFilters(
            VendorBill::query()
                ->whereIn('payment_status', ['pending', 'partial'])
                ->whereNotNull('due_date'),
            $filters
        );

        $outstandingSql = $this->outstandingAmountSql();
        $monthExpr = $this->monthKeySql('due_date');

        return $query
            ->selectRaw("{$monthExpr} as month_key")
            ->selectRaw("SUM({$this->greatestZeroSql($outstandingSql)}) as amount")
            ->groupBy('month_key')
            ->orderBy('month_key')
            ->get()
            ->map(fn ($row) => [
                'month_key' => $row->month_key,
                'month' => $this->formatMonthKey((string) $row->month_key),
                'amount' => round((float) $row->amount, 2),
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function tourWiseProfit(array $filters = []): Collection
    {
        $tourQuery = Tour::query()->orderBy('departure_date');

        if (! empty($filters['tour_id'])) {
            $tourQuery->where('id', $filters['tour_id']);
        }

        if (! empty($filters['tour_status'])) {
            $tourQuery->where('status', $filters['tour_status']);
        }

        if (! empty($filters['departure_from'])) {
            $tourQuery->whereDate('departure_date', '>=', $filters['departure_from']);
        }

        if (! empty($filters['departure_to'])) {
            $tourQuery->whereDate('departure_date', '<=', $filters['departure_to']);
        }

        return $tourQuery->get()->map(function (Tour $tour) use ($filters) {
            $tourFilters = array_merge($filters, ['tour_id' => $tour->id]);

            $sales = (float) $this->applyInvoiceFilters(Invoice::query(), $tourFilters)->sum('total_amount');
            $cost = (float) $this->applyVendorBillFilters(VendorBill::query(), $tourFilters)->sum('bill_amount');
            $profit = $sales - $cost;

            return [
                'tour_id' => $tour->id,
                'tour_code' => $tour->tour_code,
                'tour_name' => $tour->name,
                'departure_date' => $tour->departure_date?->toDateString(),
                'status' => $tour->status?->value ?? $tour->status,
                'sales_value' => round($sales, 2),
                'vendor_cost' => round($cost, 2),
                'gross_profit' => round($profit, 2),
                'gp_percent' => $sales > 0 ? round(($profit / $sales) * 100, 1) : 0.0,
            ];
        });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function tourCashGap(array $filters = [], int $daysThreshold = 30): Collection
    {
        return $this->tourWiseProfit($filters)->map(function (array $row) use ($filters, $daysThreshold) {
            $tourFilters = array_merge($filters, ['tour_id' => $row['tour_id']]);

            $receivable = (float) $this->applyInvoiceFilters(
                Invoice::query()->whereIn('customer_payment_status', ['pending', 'partial']),
                $tourFilters
            )->sum('balance_amount');

            $payable = $this->sumVendorOutstanding(
                $this->applyVendorBillFilters(
                    VendorBill::query()->whereIn('payment_status', ['pending', 'partial']),
                    $tourFilters
                )
            );

            $gap = $receivable - $payable;
            $departure = $row['departure_date'] ? Carbon::parse($row['departure_date']) : null;
            $daysToDeparture = $departure ? now()->startOfDay()->diffInDays($departure, false) : null;

            return array_merge($row, [
                'balance_receivable' => round($receivable, 2),
                'vendor_payable' => round($payable, 2),
                'cash_gap' => round($gap, 2),
                'is_negative' => $gap < 0,
                'days_to_departure' => $daysToDeparture,
                'is_urgent' => $gap < 0 && $daysToDeparture !== null && $daysToDeparture <= $daysThreshold,
            ]);
        });
    }

    /**
     * @return Collection<int, array{month: string, month_key: string, revenue: float, cost: float, gross_profit: float, gp_percent: float}>
     */
    public function departureMonthProfit(array $filters = []): Collection
    {
        $tourQuery = Tour::query()
            ->whereIn('status', [TourStatus::Departed->value, TourStatus::Cancelled->value]);

        if (! empty($filters['tour_id'])) {
            $tourQuery->where('id', $filters['tour_id']);
        }

        if (! empty($filters['departure_from'])) {
            $tourQuery->whereDate('departure_date', '>=', $filters['departure_from']);
        }

        if (! empty($filters['departure_to'])) {
            $tourQuery->whereDate('departure_date', '<=', $filters['departure_to']);
        }

        $tours = $tourQuery->get();

        return $tours
            ->groupBy(fn (Tour $tour) => $tour->departure_date?->format('Y-m') ?? 'unknown')
            ->map(function (Collection $monthTours, string $monthKey) use ($filters) {
                $revenue = 0.0;
                $cost = 0.0;

                foreach ($monthTours as $tour) {
                    $tourFilters = array_merge($filters, ['tour_id' => $tour->id]);
                    $revenue += (float) $this->applyInvoiceFilters(Invoice::query(), $tourFilters)->sum('total_amount');
                    $cost += (float) $this->applyVendorBillFilters(VendorBill::query(), $tourFilters)->sum('bill_amount');
                }

                $profit = $revenue - $cost;

                return [
                    'month_key' => $monthKey,
                    'month' => $monthKey !== 'unknown'
                        ? Carbon::createFromFormat('Y-m', $monthKey)->format('M Y')
                        : 'Unknown',
                    'revenue' => round($revenue, 2),
                    'cost' => round($cost, 2),
                    'gross_profit' => round($profit, 2),
                    'gp_percent' => $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0.0,
                ];
            })
            ->sortKeys()
            ->values();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Invoice>  $query
     * @param  array<string, mixed>  $filters
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\Invoice>
     */
    private function applyInvoiceFilters($query, array $filters)
    {
        if (! empty($filters['tour_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('tour_id', $filters['tour_id'])
                    ->orWhereHas('lead', fn ($lq) => $lq->where('tour_id', $filters['tour_id']));
            });
        }

        if (! empty($filters['sales_user_id'])) {
            $query->whereHas('lead', fn ($q) => $q->where('assigned_to', $filters['sales_user_id']));
        }

        if (! empty($filters['departure_from']) || ! empty($filters['departure_to'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereHas('tour', function ($tq) use ($filters) {
                    if (! empty($filters['departure_from'])) {
                        $tq->whereDate('departure_date', '>=', $filters['departure_from']);
                    }
                    if (! empty($filters['departure_to'])) {
                        $tq->whereDate('departure_date', '<=', $filters['departure_to']);
                    }
                })->orWhereHas('lead.tourMaster', function ($tq) use ($filters) {
                    if (! empty($filters['departure_from'])) {
                        $tq->whereDate('departure_date', '>=', $filters['departure_from']);
                    }
                    if (! empty($filters['departure_to'])) {
                        $tq->whereDate('departure_date', '<=', $filters['departure_to']);
                    }
                });
            });
        }

        if (! empty($filters['tour_status'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereHas('tour', fn ($tq) => $tq->where('status', $filters['tour_status']))
                    ->orWhereHas('lead.tourMaster', fn ($tq) => $tq->where('status', $filters['tour_status']));
            });
        }

        return $query;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\VendorBill>  $query
     * @param  array<string, mixed>  $filters
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\VendorBill>
     */
    private function applyVendorBillFilters($query, array $filters)
    {
        if (! empty($filters['tour_id'])) {
            $query->whereHas('invoice', function ($iq) use ($filters) {
                $iq->where('tour_id', $filters['tour_id'])
                    ->orWhereHas('lead', fn ($lq) => $lq->where('tour_id', $filters['tour_id']));
            });
        }

        if (! empty($filters['sales_user_id'])) {
            $query->whereHas('invoice.lead', fn ($q) => $q->where('assigned_to', $filters['sales_user_id']));
        }

        if (! empty($filters['departure_from']) || ! empty($filters['departure_to']) || ! empty($filters['tour_status'])) {
            $query->whereHas('invoice', function ($iq) use ($filters) {
                $iq->where(function ($q) use ($filters) {
                    $q->whereHas('tour', function ($tq) use ($filters) {
                        $this->applyTourDateStatusFilters($tq, $filters);
                    })->orWhereHas('lead.tourMaster', function ($tq) use ($filters) {
                        $this->applyTourDateStatusFilters($tq, $filters);
                    });
                });
            });
        }

        return $query;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Tour>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyTourDateStatusFilters($query, array $filters): void
    {
        if (! empty($filters['departure_from'])) {
            $query->whereDate('departure_date', '>=', $filters['departure_from']);
        }
        if (! empty($filters['departure_to'])) {
            $query->whereDate('departure_date', '<=', $filters['departure_to']);
        }
        if (! empty($filters['tour_status'])) {
            $query->where('status', $filters['tour_status']);
        }
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\VendorBill>  $query
     */
    private function sumVendorOutstanding($query): float
    {
        $outstandingSql = $this->outstandingAmountSql();

        return (float) $query
            ->selectRaw("SUM({$this->greatestZeroSql($outstandingSql)}) as total")
            ->value('total');
    }

    private function outstandingAmountSql(): string
    {
        return '(vendor_bills.bill_amount - COALESCE((SELECT SUM(vbp.amount) FROM vendor_bill_payments vbp WHERE vbp.vendor_bill_id = vendor_bills.id), 0))';
    }

    private function greatestZeroSql(string $expression): string
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'sqlite' => "MAX({$expression}, 0)",
            default => "GREATEST({$expression}, 0)",
        };
    }

    private function monthKeySql(string $column): string
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'sqlite' => "strftime('%Y-%m', {$column})",
            'pgsql' => "TO_CHAR({$column}, 'YYYY-MM')",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }

    private function formatMonthKey(string $monthKey): string
    {
        if ($monthKey === '' || $monthKey === 'unknown') {
            return 'Unknown';
        }

        return Carbon::createFromFormat('Y-m', $monthKey)->format('M Y');
    }
}
