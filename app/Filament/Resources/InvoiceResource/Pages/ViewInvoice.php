<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\VendorBill;
use App\Models\CustomerPayment;
use Filament\Actions\Action;
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
        return [
            \Filament\Actions\EditAction::make()
                ->label('Edit Invoice')
                ->icon('heroicon-o-pencil')
                ->button(),

            Action::make('add_customer_payment')
                ->label('Add Payment')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->button()
                ->visible(function () {
                    return $this->record->customer_balance_amount > 0;
                })
                ->form([
                    Forms\Components\Section::make('Customer Payment Details')
                        ->schema([
                            Forms\Components\TextInput::make('amount')
                                ->label('Payment Amount')
                                ->required()
                                ->numeric()
                                ->step(0.01)
                                ->prefix('$')
                                ->minValue(0.01)
                                ->rules([
                                    function () {
                                        return function (string $attribute, $value, \Closure $fail) {
                                            $remainingBalance = $this->record->customer_balance_amount;
                                            if ($value > $remainingBalance) {
                                                $fail("Payment amount cannot exceed remaining balance of LKR " . number_format($remainingBalance, 2));
                                            }
                                        };
                                    }
                                ])
                                ->helperText(function () {
                                    $remainingBalance = $this->record->customer_balance_amount;
                                    return "Remaining balance: LKR " . number_format($remainingBalance, 2);
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
                            Forms\Components\TextInput::make('receipt_number')
                                ->label('Receipt Number')
                                ->maxLength(255)
                                ->placeholder('e.g., RC202534')
                                ->unique('customer_payments', 'receipt_number')
                                ->helperText('Unique receipt number for this payment'),
                            Forms\Components\Select::make('payment_method')
                                ->label('Payment Method')
                                ->options([
                                    'bank_transfer' => 'Bank Transfer',
                                    'cash' => 'Cash',
                                    'check' => 'Check',
                                    'credit_card' => 'Credit Card',
                                    'online_payment' => 'Online Payment',
                                    'other' => 'Other',
                                ])
                                ->placeholder('Select payment method')
                                ->helperText('How the customer made this payment'),
                            Forms\Components\Textarea::make('notes')
                                ->label('Payment Notes')
                                ->rows(3)
                                ->placeholder('Any additional notes about this payment')
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                ])
                ->action(function (array $data) {
                    // Add the invoice_id to the data
                    $data['invoice_id'] = $this->record->id;
                    
                    // Create the customer payment
                    $payment = CustomerPayment::create($data);
                    
                    // Refresh the record to get updated status
                    $this->record->refresh();
                    
                    Notification::make()
                        ->success()
                        ->title('Payment added successfully')
                        ->body("Payment of LKR " . number_format($payment->amount, 2) . " has been added to invoice {$this->record->invoice_number}. " . 
                               "New status: " . ucfirst($this->record->customer_payment_status))
                        ->send();
                })
                ->modalHeading('Add Customer Payment')
                ->modalButton('Add Payment')
                ->modalWidth('2xl'),

            Action::make('create_vendor_bill')
                ->label('Add Vendor Bill')
                ->icon('heroicon-o-receipt-percent')
                ->color('success')
                ->button()
                ->form([
                    Forms\Components\Section::make('Vendor Bill Information')
                        ->schema([
                            Forms\Components\TextInput::make('vendor_name')
                                ->label('Vendor Name')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('e.g., IATA, TRAVEL BUDDY, MALAYSIA E VISA'),
                            Forms\Components\TextInput::make('vendor_bill_number')
                                ->label('Vendor Bill Number')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('e.g., XO20252345'),
                            Forms\Components\TextInput::make('bill_amount')
                                ->label('Bill Amount')
                                ->required()
                                ->numeric()
                                ->step(0.01)
                                ->prefix('$'),
                            Forms\Components\Select::make('service_type')
                                ->label('Service Type')
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
                                ->label('Service Details')
                                ->rows(3)
                                ->placeholder('Additional details about the service')
                                ->columnSpanFull(),
                        ])
                        ->columns(2),

                    Forms\Components\Section::make('Payment Information')
                        ->schema([
                            Forms\Components\Select::make('payment_status')
                                ->label('Payment Status')
                                ->options([
                                    'pending' => 'Pending',
                                    'paid' => 'Paid',
                                ])
                                ->default('pending')
                                ->required()
                                ->live(),
                            Forms\Components\DatePicker::make('payment_date')
                                ->label('Payment Date')
                                ->visible(fn($get) => $get('payment_status') === 'paid'),
                        ])
                        ->columns(2),

                    Forms\Components\Section::make('Additional Notes')
                        ->schema([
                            Forms\Components\Textarea::make('notes')
                                ->label('Notes')
                                ->rows(3)
                                ->columnSpanFull(),
                        ])
                        ->collapsible(),
                ])
                ->action(function (array $data) {
                    // Add the invoice_id to the data
                    $data['invoice_id'] = $this->record->id;
                    
                    // Create the vendor bill
                    $vendorBill = VendorBill::create($data);
                    
                    Notification::make()
                        ->success()
                        ->title('Vendor bill created successfully')
                        ->body("Vendor bill {$vendorBill->vendor_bill_number} has been added to invoice {$this->record->invoice_number}.")
                        ->send();
                })
                ->modalHeading('Add Vendor Bill')
                ->modalButton('Create Vendor Bill')
                ->modalWidth('3xl'),
        ];
    }
}