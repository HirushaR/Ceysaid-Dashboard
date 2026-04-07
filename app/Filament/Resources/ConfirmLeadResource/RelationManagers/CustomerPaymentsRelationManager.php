<?php

namespace App\Filament\Resources\ConfirmLeadResource\RelationManagers;

use App\Enums\DepositAccount;
use App\Enums\PaymentMode;
use App\Filament\Resources\InvoiceResource;
use App\Models\CustomerPayment;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CustomerPaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'customerPayments';

    protected static ?string $title = 'Customer Payments';

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
                        Forms\Components\Select::make('invoice_id')
                            ->label('Invoice')
                            ->options(function (): array {
                                return $this->getOwnerRecord()
                                    ->invoices()
                                    ->orderByDesc('id')
                                    ->get()
                                    ->mapWithKeys(fn (Invoice $inv) => [
                                        $inv->id => $inv->invoice_number.' — balance LKR '.number_format($inv->customer_balance_amount, 2),
                                    ])
                                    ->all();
                            })
                            ->required()
                            ->searchable()
                            ->live()
                            ->hiddenOn('edit')
                            ->disabledOn('edit'),
                        Forms\Components\Placeholder::make('invoice_on_record')
                            ->label('Invoice')
                            ->content(function (?Model $record): \Illuminate\Support\HtmlString {
                                if (! $record instanceof CustomerPayment) {
                                    return new \Illuminate\Support\HtmlString('—');
                                }
                                $record->loadMissing('invoice');
                                if (! $record->invoice) {
                                    return new \Illuminate\Support\HtmlString('—');
                                }
                                $url = InvoiceResource::getUrl('view', ['record' => $record->invoice]);
                                $num = e($record->invoice->invoice_number);

                                return new \Illuminate\Support\HtmlString(
                                    '<a href="'.e($url).'" class="text-primary-600 hover:underline">'.$num.'</a>'
                                );
                            })
                            ->visibleOn('edit')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('amount')
                            ->label('Payment amount')
                            ->required()
                            ->numeric()
                            ->step(0.01)
                            ->prefix('LKR')
                            ->minValue(0.01)
                            ->rules([
                                function (Get $get, ?Model $record) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                        $invoice = $this->resolveInvoiceForForm($get, $record);
                                        if (! $invoice) {
                                            return;
                                        }
                                        $excludeId = $record instanceof CustomerPayment ? $record->id : 0;
                                        $existingPayments = $invoice->customerPayments()
                                            ->where('id', '!=', $excludeId)
                                            ->sum('amount');
                                        $remainingBalance = (float) $invoice->total_amount - (float) $existingPayments;

                                        if ((float) $value > $remainingBalance + 0.0001) {
                                            $fail('Payment amount cannot exceed remaining balance of LKR '.number_format($remainingBalance, 2));
                                        }
                                    };
                                },
                            ])
                            ->helperText(function (Get $get, ?Model $record) {
                                $invoice = $this->resolveInvoiceForForm($get, $record);
                                if (! $invoice) {
                                    return 'Select an invoice first.';
                                }

                                return 'Remaining balance: LKR '.number_format($invoice->customer_balance_amount, 2);
                            }),
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Payment date')
                            ->required()
                            ->default(today())
                            ->maxDate(today()),
                        Forms\Components\Placeholder::make('receipt_number_auto')
                            ->label('Receipt number')
                            ->content(function (): string {
                                $lid = (int) $this->getOwnerRecord()->id;

                                return 'Assigned on save — CR/'.now()->year.'/'.$lid.'/{next}';
                            })
                            ->visibleOn('create')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('receipt_number')
                            ->label('Receipt number')
                            ->disabled()
                            ->dehydrated()
                            ->visibleOn('edit'),
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

    /**
     * Invoice from create form selection or from the record being edited.
     */
    protected function resolveInvoiceForForm(Get $get, ?Model $record): ?Invoice
    {
        if ($record instanceof CustomerPayment) {
            $record->loadMissing('invoice');

            return $record->invoice;
        }

        $id = $get('invoice_id');

        return $id ? Invoice::query()
            ->where('lead_id', $this->getOwnerRecord()->id)
            ->find((int) $id) : null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('receipt_number')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('invoice'))
            ->columns([
                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->url(fn (CustomerPayment $record) => $record->invoice
                        ? InvoiceResource::getUrl('view', ['record' => $record->invoice])
                        : null)
                    ->color('info'),
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
                    ->mutateFormDataUsing(function (array $data): array {
                        unset($data['invoice_on_record'], $data['receipt_number_auto'], $data['receipt_number']);

                        return $data;
                    })
                    ->using(function (array $data): Model {
                        $invoiceId = (int) ($data['invoice_id'] ?? 0);
                        unset($data['invoice_id']);
                        $invoice = Invoice::query()
                            ->where('lead_id', $this->getOwnerRecord()->id)
                            ->findOrFail($invoiceId);

                        return $invoice->customerPayments()->create($data);
                    })
                    ->visible(fn (): bool => InvoiceResource::canRecordCustomerPayments()
                        && $this->getOwnerRecord()->invoices()->get()->contains(fn (Invoice $inv) => $inv->customer_balance_amount > 0)),
            ])
            ->heading(function (): string {
                $lead = $this->getOwnerRecord();
                $invoices = $lead->invoices()->with('customerPayments')->get();
                $totalAmount = $invoices->sum('total_amount');
                $totalPaid = $invoices->sum(fn (Invoice $inv) => $inv->total_customer_payments_amount);
                $balance = $totalAmount - $totalPaid;

                return 'Customer payments — Total: LKR '.number_format($totalAmount, 2).
                    ' | Paid: LKR '.number_format($totalPaid, 2).
                    ' | Balance: LKR '.number_format($balance, 2);
            })
            ->actions([
                Tables\Actions\Action::make('downloadReceipt')
                    ->label('Receipt PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (CustomerPayment $record) => route('finance.customer-payments.pdf', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        unset($data['invoice_id'], $data['invoice_on_record'], $data['receipt_number_auto']);

                        return $data;
                    }),
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
