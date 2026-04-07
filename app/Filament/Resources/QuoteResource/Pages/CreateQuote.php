<?php

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Enums\QuoteStatus;
use App\Filament\Resources\QuoteResource;
use App\Models\Lead;
use App\Models\Quote;
use App\Services\DocumentNumberService;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateQuote extends CreateRecord
{
    protected static string $resource = QuoteResource::class;

    public function mount(): void
    {
        parent::mount();

        if (request()->filled('lead_id')) {
            $leadId = (int) request()->query('lead_id');
            $existing = Quote::query()->where('lead_id', $leadId)->first();
            if ($existing) {
                $this->redirect(QuoteResource::getUrl('view', ['record' => $existing]));

                return;
            }
            $this->form->fill([
                'lead_id' => $leadId,
                'status' => QuoteStatus::Draft->value,
            ]);
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        if ($user && ! $user->canViewAllInvoices() && ($user->isSales() || $user->isOperation())) {
            $lead = Lead::query()->find((int) ($data['lead_id'] ?? 0));
            if (! $lead || ! $user->hasLeadAssigned($lead)) {
                throw ValidationException::withMessages([
                    'lead_id' => __('You can only create quotes for leads assigned to you.'),
                ]);
            }
        }

        if (Quote::query()->where('lead_id', (int) $data['lead_id'])->exists()) {
            throw ValidationException::withMessages([
                'lead_id' => __('This lead already has a quote.'),
            ]);
        }

        $data['quote_number'] = app(DocumentNumberService::class)->nextQuoteNumberForLead((int) $data['lead_id']);
        $data['status'] = $data['status'] ?? QuoteStatus::Draft->value;

        return $data;
    }

    protected function getCreatedNotification(): ?Notification
    {
        $notification = parent::getCreatedNotification();

        if ($notification === null) {
            return null;
        }

        return $notification->actions([
            NotificationAction::make('download_pdf')
                ->label('Download PDF')
                ->url(route('finance.quotes.pdf', ['quote' => $this->getRecord()]))
                ->openUrlInNewTab(),
        ]);
    }
}
