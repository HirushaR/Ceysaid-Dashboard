<?php

namespace App\Filament\Resources\VendorBillResource\Pages;

use App\Filament\Resources\VendorBillResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewVendorBill extends ViewRecord
{
    protected static string $resource = VendorBillResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        $user = auth()->user();

        return $user && ($user->hasPermission('vendor_bills.view') || $user->isAccount() || $user->isAdmin());
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn () => route('finance.vendor-bills.pdf', ['vendorBill' => $this->record]))
                ->openUrlInNewTab(),

            EditAction::make()
                ->visible(fn () => auth()->user()?->canManageAccountingRecords() ?? false),
        ];
    }
}
