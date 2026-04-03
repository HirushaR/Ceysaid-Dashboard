<?php

namespace App\Observers;

use App\Models\Quote;
use App\Services\LeadActionLogger;

class QuoteObserver
{
    /** @var list<string> */
    private const IGNORE_ON_UPDATE = [
        'updated_at',
    ];

    public function created(Quote $quote): void
    {
        $quote->loadMissing('lead');
        LeadActionLogger::log(
            $quote->lead,
            'quote_created',
            'Quote '.($quote->quote_number ?? '#'.$quote->id).' created',
            null,
            [
                'quote_number' => $quote->quote_number,
                'status' => $quote->status?->value ?? (string) $quote->status,
                'quote_id' => $quote->id,
            ]
        );
    }

    public function updated(Quote $quote): void
    {
        $dirty = array_keys($quote->getDirty());
        $meaningful = array_values(array_diff($dirty, self::IGNORE_ON_UPDATE));
        if ($meaningful === []) {
            return;
        }

        $quote->loadMissing('lead');
        $original = $quote->getOriginal();
        $oldSlice = [];
        $newSlice = [];
        foreach ($meaningful as $key) {
            $oldVal = $original[$key] ?? null;
            if ($key === 'status' && $oldVal !== null && ! is_string($oldVal)) {
                $oldVal = $oldVal->value ?? (string) $oldVal;
            }
            $newVal = $quote->getAttribute($key);
            if ($key === 'status' && $newVal !== null && ! is_string($newVal)) {
                $newVal = $newVal->value ?? (string) $newVal;
            }
            $oldSlice[$key] = $oldVal;
            $newSlice[$key] = $newVal;
        }

        LeadActionLogger::log(
            $quote->lead,
            'quote_updated',
            'Quote '.($quote->quote_number ?? '#'.$quote->id).' updated',
            $oldSlice,
            $newSlice
        );
    }

    public function deleted(Quote $quote): void
    {
        $quote->loadMissing('lead');
        LeadActionLogger::log(
            $quote->lead,
            'quote_deleted',
            'Quote '.($quote->quote_number ?? '#'.$quote->id).' deleted',
            null,
            ['quote_id' => $quote->id]
        );
    }
}
