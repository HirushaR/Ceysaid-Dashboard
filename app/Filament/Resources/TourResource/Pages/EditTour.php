<?php

namespace App\Filament\Resources\TourResource\Pages;

use App\Enums\TourStatus;
use App\Filament\Resources\TourResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditTour extends EditRecord
{
    protected static string $resource = TourResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['status'] ?? null) === TourStatus::Departed->value) {
            $tour = $this->getRecord();
            $hasVendorBills = $tour->invoices()->whereHas('vendorBills')->exists();

            if (! $hasVendorBills) {
                Notification::make()
                    ->title('No vendor costs recorded')
                    ->body('This tour has no vendor bills on linked invoices. Confirm vendor costs before marking as departed.')
                    ->warning()
                    ->persistent()
                    ->send();
            }
        }

        return $data;
    }
}
