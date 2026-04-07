<?php

namespace App\Filament\Resources\VendorBillResource\Pages;

use App\Filament\Resources\VendorBillResource;
use App\Models\VendorBillLineItem;
use App\Services\DocumentNumberService;
use Filament\Resources\Pages\CreateRecord;

class CreateVendorBill extends CreateRecord
{
    protected static string $resource = VendorBillResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()?->canManageAccountingRecords() ?? false;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['vendor_bill_number'] = app(DocumentNumberService::class)->nextVendorBillNumber();
        $data['payment_status'] = 'pending';
        $data['payment_date'] = null;
        $data['payment_mode'] = null;
        $data['paid_through'] = null;
        $data['bill_amount'] = ! empty($data['lineItems']) && is_array($data['lineItems'])
            ? VendorBillLineItem::sumAmountsFromFormArray($data['lineItems'])
            : 0;

        return $data;
    }
}
