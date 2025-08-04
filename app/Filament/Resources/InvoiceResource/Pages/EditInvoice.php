<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\CustomerPayment;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        $user = auth()->user();
        return $user && ($user->hasPermission('invoices.edit') || $user->isAccount() || $user->isAdmin());
    }

    protected function getHeaderActions(): array
    {
        return [
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
                                                $fail("Payment amount cannot exceed remaining balance of $" . number_format($remainingBalance, 2));
                                            }
                                        };
                                    }
                                ])
                                ->helperText(function () {
                                    $remainingBalance = $this->record->customer_balance_amount;
                                    return "Remaining balance: $" . number_format($remainingBalance, 2);
                                })
                                ->default(function () {
                                    // Default to remaining balance if less than $100, otherwise leave empty
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
                        ->body("Payment of $" . number_format($payment->amount, 2) . " has been added to invoice {$this->record->invoice_number}. " . 
                               "New status: " . ucfirst($this->record->customer_payment_status))
                        ->send();
                })
                ->modalHeading('Add Customer Payment')
                ->modalButton('Add Payment')
                ->modalWidth('2xl'),

            Actions\DeleteAction::make(),
        ];
    }
}
