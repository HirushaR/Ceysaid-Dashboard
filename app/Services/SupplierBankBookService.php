<?php

namespace App\Services;

use App\Enums\DepositAccount;
use App\Models\Supplier;
use App\Models\VendorBill;
use App\Models\VendorBillPayment;
use Carbon\Carbon;
use Illuminate\Support\HtmlString;

class SupplierBankBookService
{
    /**
     * Chronological ledger: vendor bills increase balance (In), payments decrease (Out).
     *
     * @return list<array{date: ?\Carbon\Carbon, description: string, in: ?float, out: ?float, balance: float}>
     */
    public function rows(Supplier $supplier): array
    {
        $events = [];

        $bills = VendorBill::query()
            ->where('supplier_id', $supplier->id)
            ->with(['invoice.lead'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        foreach ($bills as $bill) {
            $lead = $bill->invoice?->lead;
            $leadTxt = $lead ? (($lead->reference_id ?: '#'.$lead->id).' — '.$lead->customer_name) : '—';
            $at = $bill->created_at ? Carbon::parse($bill->created_at) : now();
            $events[] = [
                '__ts' => $at->getTimestamp(),
                '__kind' => 0,
                '__id' => $bill->id,
                'date' => $at,
                'description' => 'Vendor bill '.$bill->vendor_bill_number.' — '.$leadTxt,
                'in' => (float) $bill->bill_amount,
                'out' => null,
            ];
        }

        $payments = VendorBillPayment::query()
            ->whereHas('vendorBill', fn ($q) => $q->where('supplier_id', $supplier->id))
            ->with(['vendorBill.invoice.lead'])
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();

        foreach ($payments as $payment) {
            $bill = $payment->vendorBill;
            $lead = $bill?->invoice?->lead;
            $leadTxt = $lead ? (($lead->reference_id ?: '#'.$lead->id).' — '.$lead->customer_name) : '—';
            $bank = $payment->paid_through
                ? (DepositAccount::tryFrom($payment->paid_through)?->label() ?? $payment->paid_through)
                : '—';
            $date = $payment->payment_date
                ? Carbon::parse($payment->payment_date)->startOfDay()
                : now()->startOfDay();
            $events[] = [
                '__ts' => $date->getTimestamp(),
                '__kind' => 1,
                '__id' => $payment->id,
                'date' => $date,
                'description' => 'Payment — '.($bill?->vendor_bill_number ?? '—').' — '.$leadTxt.' · '.$bank,
                'in' => null,
                'out' => (float) $payment->amount,
            ];
        }

        usort($events, function (array $a, array $b): int {
            foreach (['__ts', '__kind', '__id'] as $k) {
                if ($a[$k] !== $b[$k]) {
                    return $a[$k] <=> $b[$k];
                }
            }

            return 0;
        });

        $balance = 0.0;
        $out = [];
        foreach ($events as $event) {
            $balance += ($event['in'] ?? 0.0) - ($event['out'] ?? 0.0);
            $out[] = [
                'date' => $event['date'],
                'description' => $event['description'],
                'in' => $event['in'],
                'out' => $event['out'],
                'balance' => round($balance, 2),
            ];
        }

        return $out;
    }

    public function renderTableHtml(Supplier $supplier): HtmlString
    {
        $rows = $this->rows($supplier);
        $fmtInOut = function (?float $v): string {
            if ($v === null || abs($v) < 0.00001) {
                return '—';
            }

            return 'LKR '.number_format($v, 2, '.', ',');
        };
        $fmtBal = fn (float $v): string => 'LKR '.number_format($v, 2, '.', ',');

        $html = '<table style="width:100%;border-collapse:collapse;font-size:14px;">';
        $html .= '<thead><tr>';
        $html .= '<th style="text-align:left;border:1px solid #ddd;padding:10px 8px;background:#f4f6fb;color:#1f3c88;">Date</th>';
        $html .= '<th style="text-align:left;border:1px solid #ddd;padding:10px 8px;background:#f4f6fb;color:#1f3c88;">Description</th>';
        $html .= '<th style="text-align:right;border:1px solid #ddd;padding:10px 8px;background:#f4f6fb;color:#1f3c88;">In</th>';
        $html .= '<th style="text-align:right;border:1px solid #ddd;padding:10px 8px;background:#f4f6fb;color:#1f3c88;">Out</th>';
        $html .= '<th style="text-align:right;border:1px solid #ddd;padding:10px 8px;background:#f4f6fb;color:#1f3c88;">Balance</th>';
        $html .= '</tr></thead><tbody>';

        if ($rows === []) {
            $html .= '<tr><td colspan="5" style="border:1px solid #ddd;padding:10px 8px;color:#666;">No transactions.</td></tr>';
        } else {
            foreach ($rows as $row) {
                $dateStr = isset($row['date']) && $row['date'] instanceof Carbon
                    ? $row['date']->format('d.m.Y')
                    : '—';
                $html .= '<tr>';
                $html .= '<td style="border:1px solid #ddd;padding:8px;white-space:nowrap;">'.e($dateStr).'</td>';
                $html .= '<td style="border:1px solid #ddd;padding:8px;">'.e($row['description']).'</td>';
                $html .= '<td style="border:1px solid #ddd;padding:8px;text-align:right;white-space:nowrap;">'.$fmtInOut($row['in'] ?? null).'</td>';
                $html .= '<td style="border:1px solid #ddd;padding:8px;text-align:right;white-space:nowrap;">'.$fmtInOut($row['out'] ?? null).'</td>';
                $html .= '<td style="border:1px solid #ddd;padding:8px;text-align:right;white-space:nowrap;font-weight:600;">'.$fmtBal((float) $row['balance']).'</td>';
                $html .= '</tr>';
            }
        }

        $html .= '</tbody></table>';

        return new HtmlString($html);
    }
}
