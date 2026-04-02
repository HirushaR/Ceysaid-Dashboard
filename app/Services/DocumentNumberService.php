<?php

namespace App\Services;

use App\Models\DocumentSequence;
use Illuminate\Support\Facades\DB;

class DocumentNumberService
{
    public const TYPE_INVOICE = 'ainv';

    public const TYPE_VENDOR_BILL = 'vb';

    public const TYPE_QUOTE = 'quote';

    public function nextInvoiceNumber(): string
    {
        return $this->allocate(self::TYPE_INVOICE, 'AINV');
    }

    public function nextVendorBillNumber(): string
    {
        return $this->allocate(self::TYPE_VENDOR_BILL, 'VB');
    }

    public function nextQuoteNumber(): string
    {
        return $this->allocate(self::TYPE_QUOTE, 'QUOTE');
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
