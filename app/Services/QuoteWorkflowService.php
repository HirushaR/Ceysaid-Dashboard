<?php

namespace App\Services;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class QuoteWorkflowService
{
    public function transition(Quote $quote, QuoteStatus $to, User $actor): Quote
    {
        $allowed = match ($quote->status) {
            QuoteStatus::Draft => [QuoteStatus::Sent],
            QuoteStatus::Sent => [QuoteStatus::Accepted, QuoteStatus::Rejected, QuoteStatus::Expired],
            QuoteStatus::Accepted => [QuoteStatus::Converted],
            default => [],
        };
        if (! in_array($to, $allowed, true)) {
            throw ValidationException::withMessages(['status' => 'This quote transition is not allowed.']);
        }

        return DB::transaction(function () use ($quote, $to, $actor): Quote {
            $before = ['status' => $quote->status->value];
            $timestamp = match ($to) {
                QuoteStatus::Sent => 'sent_at',
                QuoteStatus::Accepted => 'accepted_at',
                QuoteStatus::Rejected => 'rejected_at',
                QuoteStatus::Expired => 'expired_at',
                default => null,
            };
            $values = ['status' => $to];
            if ($timestamp) {
                $values[$timestamp] = now();
            }
            $quote->update($values);
            $quote->actionLogs()->create([
                'user_id' => $actor->id,
                'action' => 'status_changed',
                'before' => $before,
                'after' => ['status' => $to->value],
            ]);

            return $quote->fresh();
        });
    }

    public function revise(Quote $quote, User $actor): Quote
    {
        if ($quote->status === QuoteStatus::Draft || $quote->status === QuoteStatus::Converted) {
            throw ValidationException::withMessages(['status' => 'This quote cannot be revised.']);
        }

        return DB::transaction(function () use ($quote, $actor): Quote {
            $quote->load('lineItems');
            $familyId = $quote->family_id ?: (string) Str::uuid();
            if (! $quote->family_id) {
                $quote->update(['family_id' => $familyId]);
            }
            $revision = (int) Quote::where('family_id', $familyId)->lockForUpdate()->max('revision') + 1;
            $copy = $quote->replicate(['quote_number', 'status', 'sent_at', 'accepted_at', 'rejected_at', 'expired_at']);
            $copy->fill([
                'family_id' => $familyId,
                'revision' => $revision,
                'quote_number' => $quote->quote_number.'/R'.$revision,
                'status' => QuoteStatus::Draft,
                'created_by' => $actor->id,
            ]);
            $copy->save();
            foreach ($quote->lineItems as $line) {
                $copy->lineItems()->create($line->only(['sort_order', 'description', 'quantity', 'rate', 'amount']));
            }
            $copy->actionLogs()->create(['user_id' => $actor->id, 'action' => 'revision_created', 'after' => ['source_quote_id' => $quote->id]]);

            return $copy->fresh('lineItems');
        });
    }
}
