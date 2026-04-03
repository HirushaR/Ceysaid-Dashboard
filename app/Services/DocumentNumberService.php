<?php

namespace App\Services;

use App\Models\DocumentSequence;
use Illuminate\Support\Facades\DB;

class DocumentNumberService
{
    public const TYPE_VENDOR_BILL = 'vb';

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
                ->lockForUpdate()
                ->first();

            if (! $row) {
                $row = DocumentSequence::create([
                    'type' => $type,
                    'year' => $year,
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
