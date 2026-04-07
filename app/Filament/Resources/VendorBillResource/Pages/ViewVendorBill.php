<?php

namespace App\Filament\Resources\VendorBillResource\Pages;

use App\Enums\DepositAccount;
use App\Enums\PaymentMode;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\VendorBillResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
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
            Action::make('pay_balance')
                ->label('Pay balance')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->visible(fn (): bool => InvoiceResource::canRecordVendorBills()
                    && $this->record->outstanding_amount > 0)
                ->modalHeading('Pay remaining balance')
                ->modalDescription(fn (): string => 'Outstanding: LKR '.number_format($this->record->outstanding_amount, 2))
                ->form([
                    Forms\Components\DatePicker::make('payment_date')
                        ->label('Payment date')
                        ->required()
                        ->default(now())
                        ->maxDate(now()),
                    Forms\Components\Select::make('payment_mode')
                        ->label('Payment mode')
                        ->options(PaymentMode::options())
                        ->required(),
                    Forms\Components\Select::make('paid_through')
                        ->label('Paid through')
                        ->options(DepositAccount::options())
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->record->markAsPaid(
                        $data['payment_date'] ?? null,
                        $data['payment_mode'] ?? null,
                        $data['paid_through'] ?? null
                    );
                    $this->record->refresh();
                    Notification::make()
                        ->success()
                        ->title('Balance recorded')
                        ->body('The full remaining amount was added as one payment.')
                        ->send();
                }),

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
