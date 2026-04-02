<?php

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Enums\QuoteStatus;
use App\Filament\Resources\QuoteResource;
use App\Services\DocumentNumberService;
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
}
