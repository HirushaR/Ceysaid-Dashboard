<?php

namespace App\Filament\Forms;

use App\Enums\QuoteStatus;
use App\Filament\Resources\QuoteResource;
use App\Models\Lead;
use Filament\Forms;
use Illuminate\Support\HtmlString;

/**
 * Collapsible "Quote" block for lead form views (same pattern as Attachments).
 */
final class LeadQuoteFormSection
{
    public static function make(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Quote')
            ->schema([
                Forms\Components\Placeholder::make('quote_details')
                    ->label('')
                    ->content(function (?Lead $record): HtmlString|string {
                        if (! $record) {
                            return '';
                        }

                        $record->loadMissing('quote');

                        if (! $record->quote) {
                            return new HtmlString(
                                '<p class="text-sm text-gray-500 dark:text-gray-400">No quote for this lead yet.</p>'
                            );
                        }

                        $q = $record->quote;
                        $canView = QuoteResource::canView($q);
                        $numberHtml = $canView
                            ? '<a href="'.e(QuoteResource::getUrl('view', ['record' => $q])).'" class="text-primary-600 hover:underline font-semibold">'.e($q->quote_number).'</a>'
                            : e($q->quote_number);

                        $status = $q->status instanceof QuoteStatus
                            ? $q->status
                            : QuoteStatus::tryFrom((string) $q->status);
                        $statusLabel = $status === QuoteStatus::Converted ? 'Converted' : 'Draft';

                        $quoteDate = $q->quote_date?->format('M j, Y') ?? '—';
                        $validUntil = $q->valid_until?->format('M j, Y') ?? '—';
                        $subjectHtml = $q->subject ? e($q->subject) : '—';

                        return new HtmlString(
                            '<dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">'
                            .'<div><dt class="text-gray-500 dark:text-gray-400">Quote #</dt><dd class="mt-0.5">'.$numberHtml.'</dd></div>'
                            .'<div><dt class="text-gray-500 dark:text-gray-400">Status</dt><dd class="mt-0.5 font-medium">'.e($statusLabel).'</dd></div>'
                            .'<div><dt class="text-gray-500 dark:text-gray-400">Quote date</dt><dd class="mt-0.5">'.e($quoteDate).'</dd></div>'
                            .'<div><dt class="text-gray-500 dark:text-gray-400">Valid until</dt><dd class="mt-0.5">'.e($validUntil).'</dd></div>'
                            .'<div class="sm:col-span-2"><dt class="text-gray-500 dark:text-gray-400">Subject</dt><dd class="mt-0.5">'.$subjectHtml.'</dd></div>'
                            .'</dl>'
                        );
                    }),
            ])
            ->collapsible()
            ->collapsed(false)
            ->visible(fn () => QuoteResource::canViewAny());
    }
}
