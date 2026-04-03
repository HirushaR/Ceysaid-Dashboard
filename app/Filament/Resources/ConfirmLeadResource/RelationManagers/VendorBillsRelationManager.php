<?php

namespace App\Filament\Resources\ConfirmLeadResource\RelationManagers;

use App\Enums\DepositAccount;
use App\Enums\PaymentMode;
use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Supplier;
use App\Models\VendorBill;
use App\Services\DocumentNumberService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class VendorBillsRelationManager extends RelationManager
{
    protected static string $relationship = 'vendorBills';

    protected static ?string $title = 'Vendor Bills';

    protected static ?string $recordTitleAttribute = 'vendor_name';

    public function isReadOnly(): bool
    {
        return ! InvoiceResource::canRecordVendorBills();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('invoice_id')
                    ->label('Invoice')
                    ->options(fn (): array => $this->getOwnerRecord()
                        ->invoices()
                        ->orderByDesc('id')
                        ->pluck('invoice_number', 'id')
                        ->all())
                    ->required()
                    ->searchable()
                    ->visibleOn('create'),
                Forms\Components\Placeholder::make('invoice_on_record')
                    ->label('Invoice')
                    ->content(function (?Model $record): \Illuminate\Support\HtmlString {
                        if (! $record instanceof VendorBill) {
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
                Forms\Components\TextInput::make('vendor_bill_number')
                    ->label('Vendor bill number')
                    ->maxLength(255)
                    ->disabledOn('edit')
                    ->dehydrated()
                    ->hiddenOn('create')
                    ->placeholder('Assigned when you save'),
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
                    ->visible(fn (Get $get) => $get('payment_status') === 'paid'),
                Forms\Components\Select::make('payment_mode')
                    ->label('Payment mode')
                    ->options(PaymentMode::options())
                    ->visible(fn (Get $get) => $get('payment_status') === 'paid')
                    ->required(fn (Get $get) => $get('payment_status') === 'paid'),
                Forms\Components\Select::make('paid_through')
                    ->label('Paid through')
                    ->options(DepositAccount::options())
                    ->visible(fn (Get $get) => $get('payment_status') === 'paid')
                    ->required(fn (Get $get) => $get('payment_status') === 'paid'),
                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->rows(2)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('vendor_name')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('invoice'))
            ->columns([
                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->url(fn (VendorBill $record) => $record->invoice
                        ? InvoiceResource::getUrl('view', ['record' => $record->invoice])
                        : null)
                    ->color('info'),
                Tables\Columns\TextColumn::make('vendor_name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('vendor_bill_number')
                    ->label('Bill #')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('service_type')
                    ->label('Service')
                    ->badge(),
                Tables\Columns\TextColumn::make('bill_amount')
                    ->label('Amount')
                    ->money('LKR')
                    ->sortable()
                    ->alignRight()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due')
                    ->date('M j, Y')
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\BadgeColumn::make('payment_status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'paid',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst((string) $state)),
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Payment date')
                    ->date('M j, Y')
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        unset($data['invoice_on_record']);
                        $data['vendor_bill_number'] = app(DocumentNumberService::class)->nextVendorBillNumber();
                        if (($data['payment_status'] ?? '') !== 'paid') {
                            $data['payment_date'] = null;
                            $data['payment_mode'] = null;
                            $data['paid_through'] = null;
                        }

                        return $data;
                    })
                    ->using(function (array $data): Model {
                        $invoiceId = (int) ($data['invoice_id'] ?? 0);
                        unset($data['invoice_id']);
                        $invoice = Invoice::query()
                            ->where('lead_id', $this->getOwnerRecord()->id)
                            ->findOrFail($invoiceId);

                        return $invoice->vendorBills()->create($data);
                    })
                    ->visible(fn (): bool => $this->getOwnerRecord()->invoices()->exists()),
            ])
            ->heading(function (): string {
                $owner = $this->getOwnerRecord();
                $vendorBills = $owner->vendorBills()->get();
                $totalAmount = $vendorBills->sum('bill_amount');
                $paidAmount = $vendorBills->where('payment_status', 'paid')->sum('bill_amount');
                $unpaidAmount = $vendorBills->where('payment_status', 'pending')->sum('bill_amount');

                return 'Vendor Bills — Total: LKR '.number_format($totalAmount, 2).
                    ' | Paid: LKR '.number_format($paidAmount, 2).
                    ' | Unpaid: LKR '.number_format($unpaidAmount, 2);
            })
            ->actions([
                Tables\Actions\Action::make('mark_paid')
                    ->label('Mark paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\DatePicker::make('payment_date')->required()->default(now()),
                        Forms\Components\Select::make('payment_mode')->options(PaymentMode::options())->required(),
                        Forms\Components\Select::make('paid_through')->options(DepositAccount::options())->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->markAsPaid(
                            $data['payment_date'] ?? null,
                            $data['payment_mode'] ?? null,
                            $data['paid_through'] ?? null
                        );
                        \Filament\Notifications\Notification::make()->success()->title('Marked paid')->send();
                    })
                    ->visible(fn ($record) => $record->isPending()),
                Tables\Actions\Action::make('mark_pending')
                    ->label('Mark pending')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->markAsPending();
                    })
                    ->visible(fn ($record) => $record->isPaid()),
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        unset($data['invoice_id'], $data['invoice_on_record']);

                        return $data;
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
