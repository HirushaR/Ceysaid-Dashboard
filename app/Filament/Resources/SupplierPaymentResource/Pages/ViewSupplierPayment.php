<?php

namespace App\Filament\Resources\SupplierPaymentResource\Pages;

use App\Filament\Resources\SupplierPaymentResource;
use Filament\Resources\Pages\ViewRecord;

class ViewSupplierPayment extends ViewRecord
{
    protected static string $resource = SupplierPaymentResource::class;

    protected function resolveRecord($key): \Illuminate\Database\Eloquent\Model
    {
        return static::getResource()::getEloquentQuery()
            ->with([
                'allocations.vendorBill.invoice.lead',
                'allocations.vendorBill.vendorBillPayments',
            ])
            ->findOrFail($key);
    }
}
