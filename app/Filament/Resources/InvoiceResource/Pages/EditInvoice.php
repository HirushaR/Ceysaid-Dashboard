<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Enums\DepositAccount;
use App\Enums\PaymentMode;
use App\Filament\Resources\InvoiceResource;
use App\Models\CustomerPayment;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()?->canManageAccountingRecords() ?? false;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->getRecord()->loadMissing([
            'lineItems' => fn ($q) => $q->orderBy('sort_order'),
        ]);

        $data['lineItems'] = $this->getRecord()->lineItems->map(fn ($l) => [
            'lead_cost_id' => $l->lead_cost_id,
            'description' => $l->description,
            'customer_details' => $l->customer_details,
            'quantity' => $l->quantity,
            'rate' => $l->rate,
        ])->values()->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $lineItems = $data['lineItems'] ?? [];
        unset($data['lineItems']);

        $record->update($data);

        if (is_array($lineItems)) {
            $record->replaceLineItemsFromFormState($lineItems);
        }

        return $record;
    }

    protected function afterSave(): void
    {
        $this->record->recalculateTotalsFromLineItems();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('add_customer_payment')
                ->label('Add payment receipt')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->button()
                ->visible(function () {
                    $u = auth()->user();

                    return $u && $u->canManageAccountingRecords()
                        && $this->record->customer_balance_amount > 0;
                })
                ->form([
                    Forms\Components\Section::make('Payment receipt')
                        ->schema([
                            Forms\Components\TextInput::make('amount')
                                ->label('Payment Amount')
                                ->required()
                                ->numeric()
                                ->step(0.01)
                                ->prefix('LKR')
                                ->minValue(0.01)
                                ->rules([
                                    function () {
                                        return function (string $attribute, $value, \Closure $fail) {
                                            $remainingBalance = $this->record->customer_balance_amount;
                                            if ($value > $remainingBalance) {
                                                $fail('Payment amount cannot exceed remaining balance of LKR '.number_format($remainingBalance, 2));
                                            }
                                        };
                                    },
                                ])
                                ->helperText(function () {
                                    $remainingBalance = $this->record->customer_balance_amount;

                                    return 'Remaining balance: LKR '.number_format($remainingBalance, 2);
                                })
                                ->default(function () {
                                    // Default to remaining balance if less than LKR 100, otherwise leave empty
                                    $balance = $this->record->customer_balance_amount;

                                    return $balance <= 100 ? $balance : null;
                                }),
                            Forms\Components\DatePicker::make('payment_date')
                                ->label('Payment Date')
                                ->required()
                                ->default(today())
                                ->maxDate(today())
                                ->helperText('When the payment was received'),
                            Forms\Components\Placeholder::make('receipt_number_auto')
                                ->label('Receipt number')
                                ->content(function (): string {
                                    $this->record->loadMissing('lead');
                                    $lid = (int) ($this->record->lead_id ?? 0);
                                    if ($lid === 0) {
                                        return 'Assigned automatically when you save.';
                                    }

                                    return 'Assigned automatically on save — CR/'.now()->year.'/'.$lid.'/…';
                                })
                                ->columnSpanFull(),
                            Forms\Components\Select::make('payment_method')
                                ->label('Payment mode')
                                ->options(PaymentMode::options())
                                ->required(),
                            Forms\Components\Select::make('deposit_to')
                                ->label('Deposit to')
                                ->options(DepositAccount::options())
                                ->required(),
                            Forms\Components\Textarea::make('notes')
                                ->label('Payment Notes')
                                ->rows(3)
                                ->placeholder('Any additional notes about this payment')
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                ])
                ->action(function (array $data) {
                    unset($data['receipt_number_auto']);
                    $data['invoice_id'] = $this->record->id;
                    unset($data['receipt_number']);

                    $payment = CustomerPayment::create($data);

                    // Refresh the record to get updated status
                    $this->record->refresh();

                    Notification::make()
                        ->success()
                        ->title('Payment added successfully')
                        ->body('Payment of LKR '.number_format($payment->amount, 2)." has been added to invoice {$this->record->invoice_number}. ".
                               'New status: '.ucfirst($this->record->customer_payment_status))
                        ->send();
                })
                ->modalHeading('Add payment receipt')
                ->modalButton('Save receipt')
                ->modalWidth('2xl'),

            Actions\DeleteAction::make(),
        ];
    }
}
