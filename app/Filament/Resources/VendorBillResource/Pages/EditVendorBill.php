<?php

namespace App\Filament\Resources\VendorBillResource\Pages;

use App\Filament\Resources\VendorBillResource;
use App\Models\VendorBillLineItem;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditVendorBill extends EditRecord
{
    protected static string $resource = VendorBillResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()?->canManageAccountingRecords() ?? false;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();
        if (! empty($data['lineItems']) && is_array($data['lineItems'])) {
            $data['bill_amount'] = VendorBillLineItem::sumAmountsFromFormArray($data['lineItems']);
        }

        $record->loadMissing('vendorBillPayments');
        $totalPaid = (float) $record->vendorBillPayments->sum('amount');
        $newAmount = (float) ($data['bill_amount'] ?? $record->bill_amount);
        if ($newAmount + 0.0001 < $totalPaid) {
            throw ValidationException::withMessages([
                'bill_amount' => __('Bill amount cannot be less than total payments recorded (LKR :amount).', [
                    'amount' => number_format($totalPaid, 2),
                ]),
            ]);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
