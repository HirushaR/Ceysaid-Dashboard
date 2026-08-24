<?php

namespace App\Filament\Resources\VendorBillResource\RelationManagers;

use App\Enums\DepositAccount;
use App\Enums\PaymentMode;
use App\Filament\Resources\InvoiceResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class VendorBillPaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'vendorBillPayments';

    protected static ?string $title = 'Vendor payments';

    protected static ?string $recordTitleAttribute = 'id';

    public function isReadOnly(): bool
    {
        return ! InvoiceResource::canRecordVendorBills();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Payment to supplier')
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
                                        $bill = $this->getOwnerRecord();
                                        $excludeId = (int) ($this->getMountedTableActionRecord()?->id ?? 0);
                                        $paid = $bill->vendorBillPayments()
                                            ->where('id', '!=', $excludeId)
                                            ->sum('amount');
                                        $remaining = max(0, (float) $bill->bill_amount - (float) $paid);

                                        if ((float) $value > $remaining + 0.0001) {
                                            $fail('Payment cannot exceed remaining balance of LKR '.number_format($remaining, 2));
                                        }
                                    };
                                },
                            ])
                            ->helperText(function (): string {
                                $bill = $this->getOwnerRecord();

                                return 'Remaining balance: LKR '.number_format($bill->outstanding_amount, 2);
                            }),
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Payment date')
                            ->required()
                            ->default(today())
                            ->maxDate(today()),
                        Forms\Components\Select::make('payment_mode')
                            ->label('Payment mode')
                            ->options(PaymentMode::options())
                            ->required(),
                        Forms\Components\Select::make('paid_through')
                            ->label('Paid through')
                            ->options(DepositAccount::options())
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
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
                Tables\Columns\TextColumn::make('supplierPayment.payment_number')
                    ->label('Supplier payment')
                    ->placeholder('Single bill / legacy')
                    ->copyable(),
                Tables\Columns\TextColumn::make('payment_mode')
                    ->label('Mode')
                    ->formatStateUsing(fn ($state) => PaymentMode::tryFrom((string) $state)?->label() ?? (string) $state),
                Tables\Columns\TextColumn::make('paid_through')
                    ->label('Paid through')
                    ->formatStateUsing(fn ($state) => DepositAccount::tryFrom((string) $state)?->label() ?? (string) $state),
                Tables\Columns\TextColumn::make('notes')
                    ->limit(40)
                    ->toggleable(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('pay_balance')
                    ->label('Pay balance')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (): bool => ! $this->isReadOnly()
                        && $this->getOwnerRecord()->outstanding_amount > 0)
                    ->modalHeading('Pay remaining balance')
                    ->modalDescription(fn (): string => 'Outstanding: LKR '.number_format($this->getOwnerRecord()->outstanding_amount, 2))
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
                        $bill = $this->getOwnerRecord();
                        $bill->markAsPaid(
                            $data['payment_date'] ?? null,
                            $data['payment_mode'] ?? null,
                            $data['paid_through'] ?? null
                        );
                        Notification::make()
                            ->success()
                            ->title('Balance recorded')
                            ->send();
                    }),
                Tables\Actions\CreateAction::make()
                    ->label('Record payment')
                    ->visible(fn (): bool => $this->getOwnerRecord()->outstanding_amount > 0),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record): bool => $record->supplier_payment_id === null),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record): bool => $record->supplier_payment_id === null),
            ])
            ->bulkActions([])
            ->heading(function (): string {
                $bill = $this->getOwnerRecord();

                return 'Payments — Bill: LKR '.number_format((float) $bill->bill_amount, 2).
                    ' | Paid: LKR '.number_format($bill->total_paid_amount, 2).
                    ' | Balance: LKR '.number_format($bill->outstanding_amount, 2);
            })
            ->defaultSort('payment_date', 'desc');
    }
}
