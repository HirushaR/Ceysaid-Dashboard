<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Quote;
use App\Services\DocumentNumberService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return InvoiceResource::canCreate();
    }

    public function mount(): void
    {
        parent::mount();

        if (request()->filled('quote_id')) {
            $quote = Quote::with(['lineItems' => fn ($q) => $q->orderBy('sort_order')])->find((int) request()->query('quote_id'));
            if ($quote) {
                $this->form->fill(array_merge(
                    [
                        'lead_id' => $quote->lead_id,
                        'quote_id' => $quote->id,
                    ],
                    $quote->attributesForInvoiceForm()
                ));
            }
        } elseif (request()->filled('lead_id')) {
            $leadId = (int) request()->query('lead_id');
            $this->form->fill(array_merge(
                ['lead_id' => $leadId],
                Quote::invoiceFormStateForLeadId($leadId)
            ));
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['invoice_number'] = app(DocumentNumberService::class)->nextInvoiceNumberForLead((int) $data['lead_id']);
        $data['invoice_date'] = $data['invoice_date'] ?? now()->toDateString();
        $data['due_date'] = $data['due_date'] ?? now()->toDateString();
        $data['total_amount'] = $data['total_amount'] ?? 0;

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $lineItems = $data['lineItems'] ?? [];
        unset($data['lineItems']);

        /** @var Invoice $record */
        $record = static::getModel()::create($data);
        $record->replaceLineItemsFromFormState(is_array($lineItems) ? $lineItems : []);

        return $record;
    }

    protected function afterCreate(): void
    {
        $this->record->recalculateTotalsFromLineItems();
    }
}
