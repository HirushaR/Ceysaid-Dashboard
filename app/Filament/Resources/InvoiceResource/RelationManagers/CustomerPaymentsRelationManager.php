<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use App\Enums\DepositAccount;
use App\Enums\PaymentMode;
use App\Filament\Resources\InvoiceResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CustomerPaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'customerPayments';

    protected static ?string $recordTitleAttribute = 'receipt_number';

    public function isReadOnly(): bool
    {
        return ! InvoiceResource::canRecordCustomerPayments();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
                                        $invoice = $this->getOwnerRecord();
                                        $existingPayments = $invoice->customerPayments()
                                            ->where('id', '!=', $this->getMountedTableActionRecord()?->id ?? 0)
                                            ->sum('amount');
                                        $remainingBalance = $invoice->total_amount - $existingPayments;

                                        if ($value > $remainingBalance) {
                                            $fail('Payment amount cannot exceed remaining balance of LKR '.number_format($remainingBalance, 2));
                                        }
                                    };
                                },
                            ])
                            ->helperText(function () {
                                $invoice = $this->getOwnerRecord();

                                return 'Remaining balance: LKR '.number_format($invoice->customer_balance_amount, 2);
                            }),
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Payment date')
                            ->required()
                            ->default(today())
                            ->maxDate(today()),
                        Forms\Components\TextInput::make('receipt_number')
                            ->label('Receipt number')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
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
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('LKR')
                    ->sortable()
                    ->alignRight()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('receipt_number')
                    ->label('Receipt #')
                    ->searchable()
                    ->placeholder('—'),
                Tables\Columns\BadgeColumn::make('payment_method')
                    ->label('Mode')
                    ->formatStateUsing(fn ($state) => PaymentMode::tryFrom((string) $state)?->label() ?? (string) $state),
                Tables\Columns\TextColumn::make('deposit_to')
                    ->label('Deposit to')
                    ->formatStateUsing(fn ($state) => DepositAccount::tryFrom((string) $state)?->label() ?? (string) $state),
                Tables\Columns\TextColumn::make('notes')
                    ->limit(30)
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Payment mode')
                    ->options(PaymentMode::options()),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Record payment')
                    ->modalHeading('Record customer payment')
                    ->successNotificationTitle('Payment saved')
                    ->visible(function () {
                        $invoice = $this->getOwnerRecord();

                        return InvoiceResource::canRecordCustomerPayments()
                            && $invoice->customer_balance_amount > 0;
                    }),
            ])
            ->heading(function () {
                $invoice = $this->getOwnerRecord();
                $totalPaid = $invoice->total_customer_payments_amount;
                $totalAmount = $invoice->total_amount;
                $balance = $invoice->customer_balance_amount;

                return 'Customer payments — Total: LKR '.number_format($totalAmount, 2).
                    ' | Paid: LKR '.number_format($totalPaid, 2).
                    ' | Balance: LKR '.number_format($balance, 2);
            })
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('payment_date', 'desc');
    }
}
