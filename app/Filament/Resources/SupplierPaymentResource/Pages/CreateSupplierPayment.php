<?php

namespace App\Filament\Resources\SupplierPaymentResource\Pages;

use App\Filament\Resources\SupplierPaymentResource;
use App\Services\RecordSupplierPaymentService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSupplierPayment extends CreateRecord
{
    protected static string $resource = SupplierPaymentResource::class;

    public function mount(): void
    {
        parent::mount();

        if (request()->filled('supplier_id')) {
            $this->form->fill([
                'supplier_id' => (int) request()->query('supplier_id'),
                'payment_date' => now()->toDateString(),
                'allocations' => [],
            ]);
        }
    }

    protected function handleRecordCreation(array $data): Model
    {
        return app(RecordSupplierPaymentService::class)->record($data);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Supplier payment recorded';
    }
}
