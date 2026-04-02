<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Services\DocumentNumberService;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()?->canManageAccountingRecords() ?? false;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['invoice_number'] = app(DocumentNumberService::class)->nextInvoiceNumber();
        $data['invoice_date'] = $data['invoice_date'] ?? now()->toDateString();
        $data['due_date'] = $data['due_date'] ?? now()->toDateString();
        $data['total_amount'] = $data['total_amount'] ?? 0;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->recalculateTotalsFromLineItems();
    }
}
