<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\VendorBill;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class FinanceCashFlowService
{
    /** @return array<string, array{label:string,end:string,incoming:float,outgoing:float,net:float}> */
    public function forecast(CarbonInterface $asOf): array
    {
        $start = $asOf->copy()->startOfDay();
        $windows = [
            'day' => ['Next day', $start->copy()->addDay()],
            'week' => ['Next 7 days', $start->copy()->addDays(7)],
            'month' => ['Next 30 days', $start->copy()->addDays(30)],
        ];

        return collect($windows)->mapWithKeys(function (array $window, string $key) use ($start): array {
            [$label, $end] = $window;
            $incoming = $this->incomingBetween($start, $end);
            $outgoing = $this->outgoingBetween($start, $end);

            return [$key => [
                'label' => $label,
                'end' => $end->format('d M Y'),
                'incoming' => $incoming,
                'outgoing' => $outgoing,
                'net' => round($incoming - $outgoing, 2),
            ]];
        })->all();
    }

    /** @return array{incoming:float,outgoing:float,incoming_count:int,outgoing_count:int} */
    public function overdue(CarbonInterface $asOf): array
    {
        $before = $asOf->copy()->startOfDay();
        $invoices = Invoice::query()->where('balance_amount', '>', 0)->whereDate('due_date', '<', $before)->get();
        $bills = $this->openBills()->whereDate('due_date', '<', $before)->get();

        return [
            'incoming' => round((float) $invoices->sum('balance_amount'), 2),
            'outgoing' => $this->sumOutstandingBills($bills),
            'incoming_count' => $invoices->count(),
            'outgoing_count' => $bills->count(),
        ];
    }

    /** @return Collection<int, Invoice> */
    public function nextReceivables(CarbonInterface $asOf, int $limit = 10): Collection
    {
        return Invoice::query()
            ->with('lead')
            ->where('balance_amount', '>', 0)
            ->whereDate('due_date', '>=', $asOf->copy()->startOfDay())
            ->orderBy('due_date')
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    /** @return Collection<int, VendorBill> */
    public function nextPayables(CarbonInterface $asOf, int $limit = 10): Collection
    {
        return $this->openBills()
            ->with(['supplier', 'invoice.lead', 'vendorBillPayments'])
            ->whereDate('due_date', '>=', $asOf->copy()->startOfDay())
            ->orderBy('due_date')
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    private function incomingBetween(CarbonInterface $start, CarbonInterface $end): float
    {
        return round((float) Invoice::query()
            ->where('balance_amount', '>', 0)
            ->whereDate('due_date', '>=', $start->toDateString())
            ->whereDate('due_date', '<=', $end->toDateString())
            ->sum('balance_amount'), 2);
    }

    private function outgoingBetween(CarbonInterface $start, CarbonInterface $end): float
    {
        $bills = $this->openBills()
            ->with('vendorBillPayments')
            ->whereDate('due_date', '>=', $start->toDateString())
            ->whereDate('due_date', '<=', $end->toDateString())
            ->get();

        return $this->sumOutstandingBills($bills);
    }

    private function openBills()
    {
        return VendorBill::query()->where('payment_status', '!=', 'paid');
    }

    /** @param Collection<int, VendorBill> $bills */
    private function sumOutstandingBills(Collection $bills): float
    {
        return round((float) $bills->sum(fn (VendorBill $bill) => $bill->outstanding_amount), 2);
    }
}
