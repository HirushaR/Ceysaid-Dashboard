<?php

namespace App\Filament\Resources;

use App\Enums\DepositAccount;
use App\Enums\PaymentMode;
use App\Filament\Resources\SupplierPaymentResource\Pages;
use App\Models\SupplierPayment;
use App\Models\VendorBill;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SupplierPaymentResource extends Resource
{
    protected static ?string $model = SupplierPayment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Supplier Payments';

    protected static ?string $label = 'Supplier Payment';

    protected static ?string $pluralLabel = 'Supplier Payments';

    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        return auth()->user()?->canManageAccountingRecords() ?? false;
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canManageAccountingRecords() ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['supplier', 'creator']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Payment')
                    ->schema([
                        Forms\Components\Select::make('supplier_id')
                            ->label('Supplier')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('allocations', [])),
                        Forms\Components\TextInput::make('amount')
                            ->label('Payment amount')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0.01)
                            ->prefix('LKR')
                            ->live(debounce: 500)
                            ->required(),
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Payment date')
                            ->required()
                            ->default(today())
                            ->rules([
                                fn () => function (string $attribute, $value, \Closure $fail): void {
                                    if (\Carbon\Carbon::parse($value)->startOfDay()->isAfter(now()->startOfDay())) {
                                        $fail('The payment date cannot be in the future.');
                                    }
                                },
                            ])
                            ->native(false),
                        Forms\Components\Select::make('payment_mode')
                            ->label('Payment method')
                            ->options(PaymentMode::options())
                            ->required(),
                        Forms\Components\Select::make('paid_through')
                            ->label('Paid through')
                            ->options(DepositAccount::options())
                            ->required(),
                        Forms\Components\TextInput::make('reference_number')
                            ->label('Cheque / transfer reference')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Bill allocations')
                    ->description('Allocate the complete payment across unpaid or partially paid bills from the selected supplier.')
                    ->schema([
                        Forms\Components\Repeater::make('allocations')
                            ->schema([
                                Forms\Components\Select::make('vendor_bill_id')
                                    ->label('Vendor bill')
                                    ->options(function (Get $get): array {
                                        $supplierId = $get('../../supplier_id');
                                        if (! $supplierId) {
                                            return [];
                                        }

                                        return VendorBill::query()
                                            ->where('supplier_id', $supplierId)
                                            ->whereIn('payment_status', ['pending', 'partial'])
                                            ->with(['invoice.lead', 'vendorBillPayments'])
                                            ->orderBy('due_date')
                                            ->get()
                                            ->mapWithKeys(function (VendorBill $bill): array {
                                                $lead = $bill->invoice?->lead;
                                                $context = $lead
                                                    ? (($lead->reference_id ?: '#'.$lead->id).' — '.$lead->customer_name)
                                                    : 'No lead';

                                                return [
                                                    $bill->id => $bill->vendor_bill_number.' — '.$context.' — Balance LKR '.number_format($bill->outstanding_amount, 2),
                                                ];
                                            })
                                            ->all();
                                    })
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set): void {
                                        $bill = $state
                                            ? VendorBill::query()->with('vendorBillPayments')->find($state)
                                            : null;

                                        $set('amount', $bill ? round($bill->outstanding_amount, 2) : null);
                                    })
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                                Forms\Components\TextInput::make('amount')
                                    ->label('Allocation')
                                    ->numeric()
                                    ->step(0.01)
                                    ->minValue(0.01)
                                    ->prefix('LKR')
                                    ->live(debounce: 500)
                                    ->required(),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                            ->addActionLabel('Add another bill')
                            ->reorderable(false)
                            ->columnSpanFull(),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Placeholder::make('payment_total_summary')
                                    ->label('Payment amount')
                                    ->content(fn (Get $get): string => 'LKR '.number_format((float) ($get('amount') ?? 0), 2)),
                                Forms\Components\Placeholder::make('allocated_total_summary')
                                    ->label('Total allocated')
                                    ->content(function (Get $get): string {
                                        $allocated = collect($get('allocations') ?? [])
                                            ->sum(fn ($row) => (float) ($row['amount'] ?? 0));

                                        return 'LKR '.number_format($allocated, 2);
                                    }),
                                Forms\Components\Placeholder::make('unallocated_total_summary')
                                    ->label('Allocation balance')
                                    ->content(function (Get $get): \Illuminate\Support\HtmlString {
                                        $payment = (float) ($get('amount') ?? 0);
                                        $allocated = collect($get('allocations') ?? [])
                                            ->sum(fn ($row) => (float) ($row['amount'] ?? 0));
                                        $remaining = round($payment - $allocated, 2);
                                        $color = $remaining < 0 ? '#dc2626' : ($remaining === 0.0 ? '#16a34a' : 'inherit');
                                        $text = $remaining < 0
                                            ? 'Over-allocated by LKR '.number_format(abs($remaining), 2)
                                            : 'LKR '.number_format($remaining, 2).' left';

                                        return new \Illuminate\Support\HtmlString(
                                            '<span style="color:'.$color.';font-weight:600;">'.
                                            e($text).
                                            '</span>'
                                        );
                                    }),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Supplier payment')
                    ->schema([
                        Infolists\Components\TextEntry::make('payment_number')
                            ->label('Payment number')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('supplier.name')
                            ->label('Supplier')
                            ->placeholder('Legacy payment'),
                        Infolists\Components\TextEntry::make('payment_date')
                            ->date('M j, Y'),
                        Infolists\Components\TextEntry::make('amount')
                            ->money('LKR'),
                        Infolists\Components\TextEntry::make('payment_mode')
                            ->label('Method')
                            ->formatStateUsing(fn ($state) => PaymentMode::tryFrom((string) $state)?->label() ?? $state),
                        Infolists\Components\TextEntry::make('paid_through')
                            ->formatStateUsing(fn ($state) => DepositAccount::tryFrom((string) $state)?->label() ?? $state),
                        Infolists\Components\TextEntry::make('reference_number')
                            ->label('Cheque / transfer reference')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('creator.name')
                            ->label('Entered by')
                            ->placeholder('Migrated record'),
                        Infolists\Components\TextEntry::make('notes')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(4),
                Infolists\Components\Section::make('Bill allocations')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('allocations')
                            ->hiddenLabel()
                            ->schema([
                                Infolists\Components\TextEntry::make('vendorBill.vendor_bill_number')
                                    ->label('Vendor bill'),
                                Infolists\Components\TextEntry::make('vendorBill.invoice.invoice_number')
                                    ->label('Invoice'),
                                Infolists\Components\TextEntry::make('vendorBill.invoice.lead.reference_id')
                                    ->label('Lead')
                                    ->placeholder('—'),
                                Infolists\Components\TextEntry::make('vendorBill.bill_amount')
                                    ->label('Bill amount')
                                    ->money('LKR'),
                                Infolists\Components\TextEntry::make('amount')
                                    ->label('Allocated')
                                    ->money('LKR'),
                                Infolists\Components\TextEntry::make('bill_balance')
                                    ->label('Balance')
                                    ->money('LKR')
                                    ->state(fn ($record): float => $record->vendorBill->outstanding_amount),
                                Infolists\Components\TextEntry::make('vendorBill.payment_status')
                                    ->label('Bill status')
                                    ->badge()
                                    ->formatStateUsing(fn ($state): string => ucfirst((string) $state))
                                    ->color(fn ($state): string => match ((string) $state) {
                                        'paid' => 'success',
                                        'partial' => 'info',
                                        default => 'warning',
                                    }),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Date')
                    ->date('M j, Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_number')
                    ->label('Payment #')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->searchable()
                    ->placeholder('Legacy payment'),
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('Reference')
                    ->searchable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('payment_mode')
                    ->label('Method')
                    ->formatStateUsing(fn ($state) => PaymentMode::tryFrom((string) $state)?->label() ?? $state),
                Tables\Columns\TextColumn::make('amount')
                    ->money('LKR')
                    ->alignRight()
                    ->sortable(),
                Tables\Columns\TextColumn::make('allocations_count')
                    ->counts('allocations')
                    ->label('Bills'),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Entered by')
                    ->placeholder('Migrated'),
            ])
            ->defaultSort('payment_date', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupplierPayments::route('/'),
            'create' => Pages\CreateSupplierPayment::route('/create'),
            'view' => Pages\ViewSupplierPayment::route('/{record}'),
        ];
    }
}
