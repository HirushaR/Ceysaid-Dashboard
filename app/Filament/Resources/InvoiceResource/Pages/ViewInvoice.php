<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Enums\DepositAccount;
use App\Enums\PaymentMode;
use App\Filament\Resources\InvoiceResource;
use App\Models\CustomerPayment;
use App\Models\Supplier;
use App\Models\VendorBill;
use App\Services\DocumentNumberService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        $user = auth()->user();

        return $user && ($user->hasPermission('invoices.view') || $user->isAccount() || $user->isAdmin());
    }

    protected function getHeaderActions(): array
    {
        $canAcct = auth()->user()?->canManageAccountingRecords() ?? false;

        return [
            Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn () => route('finance.invoices.pdf', ['invoice' => $this->record]))
                ->openUrlInNewTab(),

            EditAction::make()
                ->label('Edit invoice')
                ->icon('heroicon-o-pencil')
                ->button()
                ->visible(fn () => $canAcct),

            Action::make('add_customer_payment')
                ->label('Add payment receipt')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->button()
                ->visible(fn () => $canAcct && $this->record->customer_balance_amount > 0)
                ->form([
                    Forms\Components\Section::make('Payment receipt')
                        ->schema([
                            Forms\Components\TextInput::make('amount')
                                ->label('Payment amount')
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
                                    return 'Remaining balance: LKR '.number_format($this->record->customer_balance_amount, 2);
                                })
                                ->default(function () {
                                    $balance = $this->record->customer_balance_amount;

                                    return $balance <= 100 ? $balance : null;
                                }),
                            Forms\Components\DatePicker::make('payment_date')
                                ->label('Payment date')
                                ->required()
                                ->default(today())
                                ->maxDate(today()),
                            Forms\Components\TextInput::make('receipt_number')
                                ->label('Receipt number')
                                ->maxLength(255)
                                ->unique('customer_payments', 'receipt_number')
                                ->placeholder('e.g., RC202534'),
                            Forms\Components\Select::make('payment_method')
                                ->label('Payment mode')
                                ->options(PaymentMode::options())
                                ->required(),
                            Forms\Components\Select::make('deposit_to')
                                ->label('Deposit to')
                                ->options(DepositAccount::options())
                                ->required(),
                            Forms\Components\Textarea::make('notes')
                                ->label('Notes')
                                ->rows(3)
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                ])
                ->action(function (array $data) {
                    $data['invoice_id'] = $this->record->id;
                    CustomerPayment::create($data);
                    $this->record->refresh();
                    Notification::make()
                        ->success()
                        ->title('Payment receipt saved')
                        ->body('New status: '.ucfirst($this->record->customer_payment_status))
                        ->send();
                })
                ->modalHeading('Add payment receipt')
                ->modalButton('Save receipt')
                ->modalWidth('2xl'),

            Action::make('create_vendor_bill')
                ->label('Add vendor bill')
                ->icon('heroicon-o-receipt-percent')
                ->color('success')
                ->button()
                ->visible(fn () => $canAcct)
                ->form([
                    Forms\Components\Section::make('Vendor bill')
                        ->schema([
                            Forms\Components\Select::make('supplier_id')
                                ->label('Supplier')
                                ->relationship('supplier', 'name')
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    if ($state) {
                                        $s = Supplier::find($state);
                                        if ($s) {
                                            $set('vendor_name', $s->name);
                                        }
                                    }
                                }),
                            Forms\Components\TextInput::make('vendor_name')
                                ->label('Vendor name')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('bill_amount')
                                ->label('Bill amount')
                                ->required()
                                ->numeric()
                                ->step(0.01)
                                ->prefix('LKR'),
                            Forms\Components\Select::make('service_type')
                                ->label('Service type')
                                ->options([
                                    'AIR TICKET' => 'Air Ticket',
                                    'HOTEL' => 'Hotel',
                                    'VISA' => 'Visa',
                                    'LAND PACKAGE' => 'Land Package',
                                    'INSURANCE' => 'Insurance',
                                    'OTHER' => 'Other',
                                ])
                                ->required()
                                ->searchable(),
                            Forms\Components\Textarea::make('service_details')
                                ->label('Service details')
                                ->rows(3)
                                ->columnSpanFull(),
                        ])
                        ->columns(2),

                    Forms\Components\Section::make('Vendor payment')
                        ->schema([
                            Forms\Components\Select::make('payment_status')
                                ->label('Payment status')
                                ->options([
                                    'pending' => 'Pending',
                                    'paid' => 'Paid',
                                ])
                                ->default('pending')
                                ->required()
                                ->live(),
                            Forms\Components\DatePicker::make('payment_date')
                                ->label('Payment date')
                                ->visible(fn (Forms\Get $get) => $get('payment_status') === 'paid'),
                            Forms\Components\Select::make('payment_mode')
                                ->label('Payment mode')
                                ->options(PaymentMode::options())
                                ->visible(fn (Forms\Get $get) => $get('payment_status') === 'paid')
                                ->required(fn (Forms\Get $get) => $get('payment_status') === 'paid'),
                            Forms\Components\Select::make('paid_through')
                                ->label('Paid through')
                                ->options(DepositAccount::options())
                                ->visible(fn (Forms\Get $get) => $get('payment_status') === 'paid')
                                ->required(fn (Forms\Get $get) => $get('payment_status') === 'paid'),
                        ])
                        ->columns(2),

                    Forms\Components\Section::make('Notes')
                        ->schema([
                            Forms\Components\Textarea::make('notes')
                                ->rows(3)
                                ->columnSpanFull(),
                        ])
                        ->collapsible(),
                ])
                ->action(function (array $data) {
                    $data['invoice_id'] = $this->record->id;
                    $data['vendor_bill_number'] = app(DocumentNumberService::class)->nextVendorBillNumber();
                    if (($data['payment_status'] ?? '') !== 'paid') {
                        $data['payment_date'] = null;
                        $data['payment_mode'] = null;
                        $data['paid_through'] = null;
                    }
                    VendorBill::create($data);
                    Notification::make()
                        ->success()
                        ->title('Vendor bill created')
                        ->send();
                })
                ->modalHeading('Create vendor bill')
                ->modalWidth('3xl'),
        ];
    }
}
