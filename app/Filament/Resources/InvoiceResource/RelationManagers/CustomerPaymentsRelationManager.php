<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomerPaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'customerPayments';

    protected static ?string $recordTitleAttribute = 'receipt_number';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Payment Details')
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
                                        $invoice = $this->getOwnerRecord();
                                        $existingPayments = $invoice->customerPayments()
                                            ->where('id', '!=', $this->getMountedTableActionRecord()?->id ?? 0)
                                            ->sum('amount');
                                        $remainingBalance = $invoice->total_amount - $existingPayments;
                                        
                                        if ($value > $remainingBalance) {
                                            $fail("Payment amount cannot exceed remaining balance of $" . number_format($remainingBalance, 2));
                                        }
                                    };
                                }
                            ])
                            ->helperText(function () {
                                $invoice = $this->getOwnerRecord();
                                $remainingBalance = $invoice->customer_balance_amount;
                                return "Remaining balance: $" . number_format($remainingBalance, 2);
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
                            ->unique(ignoreRecord: true)
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
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('receipt_number')
            ->columns([
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('USD')
                    ->sortable()
                    ->alignRight()
                    ->weight('bold')
                    ->color('success'),
                Tables\Columns\TextColumn::make('receipt_number')
                    ->label('Receipt #')
                    ->searchable()
                    ->copyable()
                    ->placeholder('No receipt'),
                Tables\Columns\BadgeColumn::make('payment_method')
                    ->label('Method')
                    ->colors([
                        'info' => 'bank_transfer',
                        'success' => 'cash',
                        'warning' => 'check',
                        'primary' => 'credit_card',
                        'gray' => 'other',
                    ])
                    ->formatStateUsing(fn($state) => str_replace('_', ' ', ucwords($state)))
                    ->placeholder('Not specified'),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(30)
                    ->placeholder('No notes')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Recorded')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small)
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'bank_transfer' => 'Bank Transfer',
                        'cash' => 'Cash',
                        'check' => 'Check',
                        'credit_card' => 'Credit Card',
                        'online_payment' => 'Online Payment',
                        'other' => 'Other',
                    ]),
                Tables\Filters\Filter::make('payment_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('payment_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('payment_date', '<=', $date),
                            );
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->modalHeading('Add Customer Payment')
                    ->modalButton('Add Payment')
                    ->successNotificationTitle('Payment added successfully')
                    ->visible(function () {
                        $invoice = $this->getOwnerRecord();
                        return $invoice->customer_balance_amount > 0;
                    }),
            ])
            ->heading(function () {
                $invoice = $this->getOwnerRecord();
                $totalPaid = $invoice->total_customer_payments_amount;
                $totalAmount = $invoice->total_amount;
                $balance = $invoice->customer_balance_amount;
                $paymentCount = $invoice->customerPayments->count();
                
                $heading = "Customer Payments - Total: $" . number_format($totalAmount, 2) . 
                           " | Paid: $" . number_format($totalPaid, 2) . " ({$paymentCount} payments)" .
                           " | Balance: $" . number_format($balance, 2);
                
                if ($balance <= 0) {
                    $heading .= " ✅ FULLY PAID";
                }
                
                return $heading;
            })
            ->actions([
                Tables\Actions\EditAction::make()
                    ->successNotificationTitle('Payment updated successfully'),
                Tables\Actions\DeleteAction::make()
                    ->successNotificationTitle('Payment deleted successfully'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('payment_date', 'desc')
            ->emptyStateHeading('No payments recorded')
            ->emptyStateDescription(function () {
                $invoice = $this->getOwnerRecord();
                if ($invoice->customer_balance_amount <= 0) {
                    return 'This invoice is fully paid.';
                }
                return 'Add the first customer payment to start tracking payments for this invoice.';
            })
            ->emptyStateIcon('heroicon-o-banknotes');
    }
}