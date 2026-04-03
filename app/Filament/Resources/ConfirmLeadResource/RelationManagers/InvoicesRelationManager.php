<?php

namespace App\Filament\Resources\ConfirmLeadResource\RelationManagers;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Quote;
use App\Services\DocumentNumberService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    protected static ?string $recordTitleAttribute = 'invoice_number';

    public function isReadOnly(): bool
    {
        return ! (auth()->user()?->canManageAccountingRecords() ?? false);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Invoice')
                    ->schema([
                        Forms\Components\TextInput::make('invoice_number')
                            ->label('Invoice number')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->disabledOn('edit')
                            ->dehydrated()
                            ->hiddenOn('create'),
                        Forms\Components\DatePicker::make('invoice_date')->default(now()),
                        Forms\Components\DatePicker::make('due_date')->default(now()),
                        Forms\Components\TextInput::make('terms')->default('Due on Receipt'),
                        Forms\Components\TextInput::make('subject'),
                        Forms\Components\Hidden::make('total_amount')->default(0),
                        Forms\Components\Hidden::make('quote_id'),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Line items')
                    ->schema([
                        Forms\Components\Repeater::make('lineItems')
                            ->schema([
                                Forms\Components\Hidden::make('lead_cost_id'),
                                Forms\Components\Textarea::make('description')
                                    ->required()
                                    ->rows(2)
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('customer_details')->maxLength(255),
                                Forms\Components\TextInput::make('quantity')->numeric()->default(1)->required(),
                                Forms\Components\TextInput::make('rate')
                                    ->label('Rate (LKR)')
                                    ->numeric()
                                    ->prefix('LKR')
                                    ->required(),
                            ])
                            ->defaultItems(1)
                            ->reorderable()
                            ->orderColumn('sort_order')
                            ->columns(2),
                    ])
                    ->collapsible(),
                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')->rows(3)->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('invoice_number')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['vendorBills', 'customerPayments']))
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('medium')
                    ->url(fn ($record) => $record ? InvoiceResource::getUrl('view', ['record' => $record]) : null)
                    ->color('info')
                    ->tooltip('Open invoice: customer payments, vendor bills, PDF'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Amount')
                    ->money('LKR')
                    ->sortable()
                    ->alignRight()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('total_customer_payments_amount')
                    ->label('Customer paid')
                    ->money('LKR')
                    ->alignRight()
                    ->getStateUsing(fn ($record) => $record->total_customer_payments_amount),
                Tables\Columns\TextColumn::make('customer_balance_amount')
                    ->label('Balance')
                    ->money('LKR')
                    ->alignRight()
                    ->color(fn ($record) => $record->customer_balance_amount > 0 ? 'warning' : 'success')
                    ->getStateUsing(fn ($record) => $record->customer_balance_amount),
                Tables\Columns\TextColumn::make('description')
                    ->label('Details')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->description)
                    ->placeholder('No details'),
                Tables\Columns\TextColumn::make('vendorBills')
                    ->label('Vendor bills')
                    ->formatStateUsing(function ($record) {
                        if ($record->vendorBills->isEmpty()) {
                            return 'No vendor bills';
                        }

                        return $record->vendorBills->pluck('vendor_bill_number')->join(', ');
                    })
                    ->limit(30)
                    ->tooltip(function ($record) {
                        if ($record->vendorBills->isEmpty()) {
                            return 'No vendor bills attached';
                        }

                        return $record->vendorBills->map(function ($bill) {
                            return "{$bill->vendor_name}: {$bill->vendor_bill_number} (LKR ".number_format((float) $bill->bill_amount, 2).')';
                        })->join("\n");
                    }),
                Tables\Columns\TextColumn::make('total_vendor_bills_amount')
                    ->label('Vendor amount')
                    ->money('LKR')
                    ->sortable()
                    ->alignRight()
                    ->getStateUsing(fn ($record) => $record->total_vendor_bills_amount),
                Tables\Columns\BadgeColumn::make('customer_payment_status')
                    ->label('Customer payment')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'partial',
                        'success' => 'paid',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Pending',
                        'partial' => 'Partial',
                        'paid' => 'Paid',
                        default => ucfirst((string) $state),
                    }),
                Tables\Columns\BadgeColumn::make('vendor_payment_status')
                    ->label('Vendor payment')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'partial',
                        'success' => 'paid',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Pending',
                        'partial' => 'Partial',
                        'paid' => 'Paid',
                        default => ucfirst((string) $state),
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer_payment_status')
                    ->label('Customer payment')
                    ->options([
                        'pending' => 'Pending',
                        'partial' => 'Partial',
                        'paid' => 'Paid',
                    ]),
                Tables\Filters\SelectFilter::make('vendor_payment_status')
                    ->label('Vendor payment')
                    ->options([
                        'pending' => 'Pending',
                        'partial' => 'Partial',
                        'paid' => 'Paid',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Create invoice')
                    ->fillForm(fn (): array => array_merge(
                        [
                            'invoice_date' => now(),
                            'due_date' => now(),
                            'terms' => 'Due on Receipt',
                            'total_amount' => 0,
                        ],
                        Quote::invoiceFormStateForLeadId($this->getOwnerRecord()->id)
                    ))
                    ->using(function (array $data, Table $table): Invoice {
                        $ownerId = $this->getOwnerRecord()->id;
                        $lineItems = $data['lineItems'] ?? [];
                        unset($data['lineItems']);

                        $data['lead_id'] = $ownerId;
                        $data['invoice_number'] = app(DocumentNumberService::class)->nextInvoiceNumberForLead($ownerId);
                        $data['invoice_date'] = $data['invoice_date'] ?? now()->toDateString();
                        $data['due_date'] = $data['due_date'] ?? now()->toDateString();
                        $data['total_amount'] = $data['total_amount'] ?? 0;

                        $invoice = Invoice::create($data);
                        $invoice->replaceLineItemsFromFormState(is_array($lineItems) ? $lineItems : []);

                        return $invoice->fresh();
                    })
                    ->after(function ($record) {
                        $record->refresh();
                        $record->recalculateTotalsFromLineItems();
                        $record->updateVendorPaymentStatus();
                    }),
            ])
            ->heading(function () {
                $owner = $this->getOwnerRecord();
                $invoices = $owner->invoices()->with(['customerPayments', 'vendorBills'])->get();
                $totalInvoiceAmount = $invoices->sum('total_amount');
                $customerPaidTotal = $invoices->sum(fn ($inv) => $inv->total_customer_payments_amount);
                $balanceTotal = $invoices->sum(fn ($inv) => $inv->customer_balance_amount);
                $vendorBillsTotal = $invoices->sum(fn ($inv) => $inv->total_vendor_bills_amount);
                $paidCount = $invoices->where('customer_payment_status', 'paid')->count();
                $partialCount = $invoices->where('customer_payment_status', 'partial')->count();

                return 'Invoices — Revenue: LKR '.number_format($totalInvoiceAmount, 2).
                    ' | Customer paid: LKR '.number_format($customerPaidTotal, 2).
                    ' | Balance: LKR '.number_format($balanceTotal, 2).
                    ' | Vendor bills (sum): LKR '.number_format($vendorBillsTotal, 2).
                    " | Paid invoices: {$paidCount} | Partial: {$partialCount}";
            })
            ->actions([
                Tables\Actions\Action::make('view_invoice_detail')
                    ->label('Invoice detail')
                    ->icon('heroicon-o-document-text')
                    ->color('primary')
                    ->url(fn ($record) => $record ? InvoiceResource::getUrl('view', ['record' => $record]) : null),
                Tables\Actions\EditAction::make()
                    ->mutateRecordDataUsing(function (array $data): array {
                        $id = (int) ($data['id'] ?? 0);
                        if (! $id) {
                            return $data;
                        }

                        $invoice = Invoice::query()
                            ->with(['lineItems' => fn ($q) => $q->orderBy('sort_order')])
                            ->find($id);
                        if (! $invoice) {
                            return $data;
                        }

                        $data['lineItems'] = $invoice->lineItems->map(fn ($l) => [
                            'lead_cost_id' => $l->lead_cost_id,
                            'description' => $l->description,
                            'customer_details' => $l->customer_details,
                            'quantity' => $l->quantity,
                            'rate' => $l->rate,
                        ])->values()->all();

                        return $data;
                    })
                    ->using(function (array $data, Model $record): Model {
                        $lineItems = $data['lineItems'] ?? [];
                        unset($data['lineItems']);
                        $record->update($data);
                        if (is_array($lineItems)) {
                            $record->replaceLineItemsFromFormState($lineItems);
                        }

                        return $record->fresh();
                    })
                    ->after(function ($record) {
                        $record->refresh();
                        $record->recalculateTotalsFromLineItems();
                        $record->updateVendorPaymentStatus();
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn ($record) => $record ? InvoiceResource::getUrl('view', ['record' => $record]) : null)
            ->emptyStateDescription('Create an invoice here for line items, then open Invoice detail to record customer payments, vendor bills, and download the PDF — same layout as the main invoice page.');
    }
}
