<?php

namespace App\Services;

use App\Enums\LeadStatus;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\User;
use App\Models\VendorBill;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AdminAnalyticsService
{
    public function summary(string $from, string $to, ?int $salesId = null): array
    {
        $leads = Lead::query()
            ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->when($salesId, fn (Builder $query) => $query->where('assigned_to', $salesId));

        $invoices = Invoice::query()
            ->whereDate('invoice_date', '>=', $from)
            ->whereDate('invoice_date', '<=', $to)
            ->when($salesId, fn (Builder $query) => $query->whereHas(
                'lead',
                fn (Builder $leadQuery) => $leadQuery->where('assigned_to', $salesId),
            ));

        $assigned = (clone $leads)->count();
        $converted = (clone $leads)->whereIn('status', $this->convertedStatuses())->count();
        $revenue = (float) (clone $invoices)->sum('total_amount');
        $received = (float) (clone $invoices)->sum('payment_amount');
        $costs = (float) VendorBill::query()
            ->whereHas('invoice', function (Builder $query) use ($from, $to, $salesId): void {
                $query->whereDate('invoice_date', '>=', $from)
                    ->whereDate('invoice_date', '<=', $to)
                    ->when($salesId, fn (Builder $invoiceQuery) => $invoiceQuery->whereHas(
                        'lead',
                        fn (Builder $leadQuery) => $leadQuery->where('assigned_to', $salesId),
                    ));
            })
            ->sum('bill_amount');

        return [
            'leads' => $assigned,
            'converted' => $converted,
            'conversion_rate' => $assigned ? round($converted / $assigned * 100, 1) : 0,
            'revenue' => $revenue,
            'received' => $received,
            'receivable' => $revenue - $received,
            'costs' => $costs,
            'profit' => $revenue - $costs,
        ];
    }

    public function pipeline(string $from, string $to, ?int $salesId = null): Collection
    {
        return Lead::query()
            ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->when($salesId, fn (Builder $query) => $query->where('assigned_to', $salesId))
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();
    }

    public function staff(string $from, string $to): Collection
    {
        return User::query()->where('role', 'sales')->orderBy('name')->get()->map(
            function (User $user) use ($from, $to): array {
                $leads = $user->leads()->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59']);
                $assigned = (clone $leads)->count();
                $converted = (clone $leads)->whereIn('status', $this->convertedStatuses())->count();
                $revenue = (float) Invoice::query()
                    ->whereHas('lead', fn (Builder $query) => $query->where('assigned_to', $user->id))
                    ->whereDate('invoice_date', '>=', $from)
                    ->whereDate('invoice_date', '<=', $to)
                    ->sum('total_amount');

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'assigned' => $assigned,
                    'converted' => $converted,
                    'rate' => $assigned ? round($converted / $assigned * 100, 1) : 0,
                    'revenue' => $revenue,
                    'average' => $converted ? round($revenue / $converted, 2) : 0,
                    'active' => $user->leads()->whereNotIn('status', [
                        LeadStatus::MARK_CLOSED->value,
                        LeadStatus::DOCUMENT_UPLOAD_COMPLETE->value,
                    ])->count(),
                ];
            },
        );
    }

    private function convertedStatuses(): array
    {
        return [
            LeadStatus::CONFIRMED->value,
            LeadStatus::OPERATION_COMPLETE->value,
            LeadStatus::DOCUMENT_UPLOAD_COMPLETE->value,
        ];
    }
}
