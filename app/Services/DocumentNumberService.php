<?php

namespace App\Services;

use App\Models\DocumentSequence;
use Illuminate\Support\Facades\DB;

class DocumentNumberService
{
    public const TYPE_VENDOR_BILL = 'vb';

    /** Per-lead customer payment receipt: CR/{year}/{lead_id}/{seq} */
    public const TYPE_CUSTOMER_RECEIPT = 'cr';

    /**
     * Quote numbers: QT/{year}/{lead_id} (one quote per lead).
     */
    public function nextQuoteNumberForLead(int $leadId): string
    {
        $year = (int) now()->year;

        return "QT/{$year}/{$leadId}";
    }

    /**
     * Invoice numbers: INV/{year}/{lead_id} (suffix -2, -3 if the base already exists).
     */
    public function nextInvoiceNumberForLead(int $leadId): string
    {
        return $this->nextLeadScopedNumber('invoices', 'invoice_number', 'INV', $leadId);
    }

    public function nextVendorBillNumber(): string
    {
        return $this->allocate(self::TYPE_VENDOR_BILL, 'VB');
    }

    /**
     * Customer payment receipts: CR/2026/{lead_id}/1, CR/2026/{lead_id}/2, …
     */
    public function nextCustomerReceiptNumberForLead(int $leadId): string
    {
        $year = (int) now()->year;

        return DB::transaction(function () use ($leadId, $year) {
            $row = DocumentSequence::query()
                ->where('type', self::TYPE_CUSTOMER_RECEIPT)
                ->where('year', $year)
                ->where('lead_id', $leadId)
                ->lockForUpdate()
                ->first();

            if (! $row) {
                $initial = $this->maxExistingCustomerReceiptSequenceForLead($leadId, $year);
                $row = DocumentSequence::create([
                    'type' => self::TYPE_CUSTOMER_RECEIPT,
                    'year' => $year,
                    'lead_id' => $leadId,
                    'sequence' => $initial,
                ]);
            }

            $row->increment('sequence');
            $row->refresh();

            return "CR/{$year}/{$leadId}/{$row->sequence}";
        });
    }

    private function maxExistingCustomerReceiptSequenceForLead(int $leadId, int $year): int
    {
        $prefix = "CR/{$year}/{$leadId}/";
        $max = 0;
        foreach (DB::table('customer_payments')->where('receipt_number', 'like', $prefix.'%')->pluck('receipt_number') as $rn) {
            $rn = (string) $rn;
            if (! str_starts_with($rn, $prefix)) {
                continue;
            }
            $n = (int) substr($rn, strlen($prefix));
            if ($n > $max) {
                $max = $n;
            }
        }

        return $max;
    }

    private function nextLeadScopedNumber(string $table, string $column, string $prefix, int $leadId): string
    {
        $year = (int) now()->year;
        $base = "{$prefix}/{$year}/{$leadId}";

        if (! DB::table($table)->where($column, $base)->exists()) {
            return $base;
        }

        $n = 2;
        while (DB::table($table)->where($column, "{$base}-{$n}")->exists()) {
            $n++;
        }

        return "{$base}-{$n}";
    }

    private function allocate(string $type, string $prefix): string
    {
        $year = (int) now()->year;

        return DB::transaction(function () use ($type, $prefix, $year) {
            $row = DocumentSequence::query()
                ->where('type', $type)
                ->where('year', $year)
                ->where('lead_id', 0)
                ->lockForUpdate()
                ->first();

            if (! $row) {
                $row = DocumentSequence::create([
                    'type' => $type,
                    'year' => $year,
                    'lead_id' => 0,
                    'sequence' => 0,
                ]);
            }

            $row->increment('sequence');
            $row->refresh();
            $seq = str_pad((string) $row->sequence, 5, '0', STR_PAD_LEFT);

            return "{$prefix}/{$year}/{$seq}";
        });
    }
}
