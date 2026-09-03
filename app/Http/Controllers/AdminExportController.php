<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Lead;
use App\Models\User;
use App\Services\AdminAnalyticsService;
use App\Services\AuditService;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminExportController
{
    public function __invoke(Request $request, string $type, AdminAnalyticsService $analytics, AuditService $audit): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->isAdmin() || $user->isAccount() || $user->isManager()), 403);

        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $from = $validated['from'] ?? now()->subDays(30)->toDateString();
        $to = $validated['to'] ?? today()->toDateString();
        $audit->record('export.downloaded', null, [], ['type' => $type, 'from' => $from, 'to' => $to]);

        return response()->streamDownload(
            fn () => $this->writeCsv($type, $from, $to, $user, $analytics),
            $type.'-'.$from.'-'.$to.'.csv',
            ['Content-Type' => 'text/csv'],
        );
    }

    private function writeCsv(
        string $type,
        string $from,
        string $to,
        User $user,
        AdminAnalyticsService $analytics,
    ): void {
        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF");

        match ($type) {
            'staff' => $this->writeStaff($output, $from, $to, $user, $analytics),
            'invoices' => $this->writeInvoices($output, $from, $to, $user),
            default => $this->writeLeads($output, $from, $to, $user),
        };

        fclose($output);
    }

    private function writeStaff($output, string $from, string $to, User $user, AdminAnalyticsService $analytics): void
    {
        fputcsv($output, ['Sales user', 'Assigned', 'Converted', 'Conversion %', 'Revenue', 'Average deal', 'Active']);
        $rows = $analytics->staff($from, $to);
        if (! $user->isAdmin()) {
            $rows = $rows->whereIn('id', $user->teamMembers()->pluck('id')->push($user->id));
        }

        foreach ($rows as $row) {
            $this->csv($output, [$row['name'], $row['assigned'], $row['converted'], $row['rate'], $row['revenue'], $row['average'], $row['active']]);
        }
    }

    private function writeInvoices($output, string $from, string $to, User $user): void
    {
        fputcsv($output, ['Invoice', 'Customer', 'Date', 'Due', 'Total', 'Received', 'Balance', 'Status']);
        Invoice::query()->with('lead')
            ->whereDate('invoice_date', '>=', $from)
            ->whereDate('invoice_date', '<=', $to)
            ->visibleToUser($user)
            ->chunk(200, function ($rows) use ($output): void {
                foreach ($rows as $invoice) {
                    $this->csv($output, [$invoice->invoice_number, $invoice->lead?->customer_name, $invoice->invoice_date?->toDateString(), $invoice->due_date?->toDateString(), $invoice->total_amount, $invoice->payment_amount, $invoice->balance_amount, $invoice->customer_payment_status]);
                }
            });
    }

    private function writeLeads($output, string $from, string $to, User $user): void
    {
        fputcsv($output, ['Reference', 'Customer', 'Created', 'Destination', 'Source', 'Status', 'Sales owner']);
        $query = Lead::query()->with('assignedUser')
            ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59']);
        if (! $user->isAdmin() && ! $user->isAccount()) {
            $query->where('assigned_to', $user->id);
        }

        $query->chunk(200, function ($rows) use ($output): void {
            foreach ($rows as $lead) {
                $this->csv($output, [$lead->reference_id, $lead->customer_name, $lead->created_at?->toDateString(), $lead->destination, $lead->platform, $lead->status, $lead->assignedUser?->name]);
            }
        });
    }

    private function csv($output, array $row): void
    {
        fputcsv($output, array_map(function ($value) {
            if (is_string($value) && preg_match('/^[=+\-@]/', $value)) {
                return "'".$value;
            }
            return $value;
        }, $row));
    }
}
