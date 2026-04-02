<?php

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Enums\QuoteStatus;
use App\Filament\Resources\QuoteResource;
use App\Services\DocumentNumberService;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateQuote extends CreateRecord
{
    protected static string $resource = QuoteResource::class;

    public function mount(): void
    {
        parent::mount();

        if (request()->filled('lead_id')) {
            $this->form->fill([
                'lead_id' => (int) request()->query('lead_id'),
                'status' => QuoteStatus::Draft->value,
            ]);
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['quote_number'] = app(DocumentNumberService::class)->nextQuoteNumber();
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
