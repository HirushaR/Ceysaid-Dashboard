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
        $canEditInvoice = auth()->user()?->canEditInvoices() ?? false;
        $canVendorBills = InvoiceResource::canRecordVendorBills();
        $canCustomerPayments = InvoiceResource::canRecordCustomerPayments();

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
                ->visible(fn () => $canEditInvoice),

            Action::make('add_customer_payment')
                ->label('Record customer payment')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->button()
                ->visible(fn () => $canCustomerPayments && $this->record->customer_balance_amount > 0)
                ->modalHeading(fn (): string => 'Record customer payment — '.$this->record->invoice_number)
                ->form([
                    Forms\Components\Placeholder::make('payment_context')
                        ->label('Invoice & lead')
                        ->content(function (): \Illuminate\Contracts\Support\Htmlable {
                            $this->record->loadMissing('lead');
                            $lead = $this->record->lead;
                            $lines = [
                                '<strong>Invoice:</strong> '.e($this->record->invoice_number),
                                '<strong>Balance due:</strong> LKR '.number_format((float) $this->record->customer_balance_amount, 2),
                            ];
                            if ($lead) {
                                $ref = $lead->reference_id ? e($lead->reference_id) : '#'.$lead->id;
                                $name = e($lead->customer_name ?? '—');
                                $lines[] = "<strong>Lead:</strong> {$ref} — {$name}";
                            }

                            return new \Illuminate\Support\HtmlString(implode('<br>', $lines));
                        })
                        ->columnSpanFull(),
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
                                ->label('Notes')
                                ->rows(3)
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                ])
                ->action(function (array $data) {
                    unset($data['receipt_number_auto']);
                    $data['invoice_id'] = $this->record->id;
                    unset($data['receipt_number']);
                    CustomerPayment::create($data);
                    $this->record->refresh();
                    Notification::make()
                        ->success()
                        ->title('Payment recorded')
                        ->body('Customer payment status: '.ucfirst($this->record->customer_payment_status).'.')
                        ->send();

                    $this->redirect(InvoiceResource::getUrl('view', ['record' => $this->record]));
                })
                ->modalButton('Save payment')
                ->modalWidth('2xl'),

            Action::make('create_vendor_bill')
                ->label('Create vendor bill')
                ->icon('heroicon-o-receipt-percent')
                ->color('success')
                ->button()
                ->visible(fn () => $canVendorBills)
                ->modalHeading(fn (): string => 'Create vendor bill — '.$this->record->invoice_number)
                ->form([
                    Forms\Components\Placeholder::make('invoice_lead_context')
                        ->label('Invoice & lead')
                        ->content(function (): \Illuminate\Contracts\Support\Htmlable {
                            $this->record->loadMissing('lead');
                            $lead = $this->record->lead;
                            $lines = [
                                '<strong>Invoice:</strong> '.e($this->record->invoice_number),
                            ];
                            if ($lead) {
                                $ref = $lead->reference_id ? e($lead->reference_id) : '#'.$lead->id;
                                $name = e($lead->customer_name ?? '—');
                                $lines[] = "<strong>Lead:</strong> {$ref} — {$name}";
                            } else {
                                $lines[] = '<strong>Lead:</strong> —';
                            }

                            return new \Illuminate\Support\HtmlString(implode('<br>', $lines));
                        })
                        ->columnSpanFull(),
                    Forms\Components\Section::make('Vendor bill')
                        ->schema([
                            Forms\Components\Select::make('supplier_id')
                                ->label('Supplier')
                                ->options(fn () => Supplier::query()->orderBy('name')->pluck('name', 'id'))
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
                            Forms\Components\DatePicker::make('due_date')
                                ->label('Due date')
                                ->nullable()
                                ->native(false),
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
                    $this->record->refresh();
                    $this->record->updateVendorPaymentStatus();

                    Notification::make()
                        ->success()
                        ->title('Vendor bill created')
                        ->body('Bill '.$data['vendor_bill_number'].' is linked to this invoice.')
                        ->send();

                    $this->redirect(InvoiceResource::getUrl('view', ['record' => $this->record]));
                })
                ->modalWidth('3xl'),
        ];
    }
}
