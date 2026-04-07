<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use App\Enums\DepositAccount;
use App\Enums\PaymentMode;
use App\Filament\Resources\InvoiceResource;
use App\Models\Supplier;
use App\Models\VendorBill;
use App\Services\DocumentNumberService;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VendorBillsRelationManager extends RelationManager
{
    protected static string $relationship = 'vendorBills';

    protected static ?string $recordTitleAttribute = 'vendor_name';

    public function isReadOnly(): bool
    {
        return ! InvoiceResource::canRecordVendorBills();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
                    ->required()
                    ->maxLength(255)
                    ->disabledOn('edit')
                    ->dehydrated(),
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
                Forms\Components\Section::make('Vendor payments')
                    ->description('Use Pay balance for the full remainder, or open the full vendor bill for installments.')
                    ->schema([
                        Forms\Components\Placeholder::make('payment_summary')
                            ->label('Summary')
                            ->content(function (?VendorBill $record): string {
                                if (! $record) {
                                    return '—';
                                }
                                $record->loadMissing('vendorBillPayments');

                                return 'Bill: LKR '.number_format((float) $record->bill_amount, 2).
                                    ' | Paid: LKR '.number_format($record->total_paid_amount, 2).
                                    ' | Balance: LKR '.number_format($record->outstanding_amount, 2).
                                    ' | Status: '.ucfirst((string) $record->payment_status);
                            })
                            ->columnSpanFull(),
                        Forms\Components\Actions::make([
                            FormAction::make('pay_balance_detail')
                                ->label('Pay balance')
                                ->icon('heroicon-o-banknotes')
                                ->color('success')
                                ->visible(fn (VendorBill $record): bool => InvoiceResource::canRecordVendorBills()
                                    && $record->outstanding_amount > 0)
                                ->modalHeading('Pay remaining balance')
                                ->modalDescription(fn (VendorBill $record): string => 'Outstanding: LKR '.number_format($record->outstanding_amount, 2))
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
                                ->action(function (VendorBill $record, array $data): void {
                                    $record->markAsPaid(
                                        $data['payment_date'] ?? null,
                                        $data['payment_mode'] ?? null,
                                        $data['paid_through'] ?? null
                                    );
                                    $record->refresh();
                                    \Filament\Notifications\Notification::make()
                                        ->success()
                                        ->title('Balance recorded')
                                        ->body('The full remaining amount was added as one payment.')
                                        ->send();
                                }),
                        ])
                            ->key('invoice_vendor_bill_pay_balance'),
                    ])
                    ->visibleOn('edit')
                    ->columnSpanFull(),
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
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('vendorBillPayments'))
            ->columns([
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
                Tables\Columns\TextColumn::make('total_paid_amount')
                    ->label('Paid')
                    ->money('LKR')
                    ->alignRight(),
                Tables\Columns\TextColumn::make('outstanding_amount')
                    ->label('Balance')
                    ->money('LKR')
                    ->alignRight()
                    ->color(fn (VendorBill $record) => $record->outstanding_amount > 0 ? 'warning' : 'success'),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due')
                    ->date('M j, Y')
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\BadgeColumn::make('payment_status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'partial',
                        'success' => 'paid',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst((string) $state)),
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Last payment')
                    ->date('M j, Y')
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_status')
                    ->options([
                        'pending' => 'Pending',
                        'partial' => 'Partial',
                        'paid' => 'Paid',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['vendor_bill_number'] = app(DocumentNumberService::class)->nextVendorBillNumber();
                        $data['payment_status'] = 'pending';
                        $data['payment_date'] = null;
                        $data['payment_mode'] = null;
                        $data['paid_through'] = null;

                        return $data;
                    }),
            ])
            ->heading(function () {
                $ownerRecord = $this->getOwnerRecord();
                $vendorBills = $ownerRecord->vendorBills()->with('vendorBillPayments')->get();
                $totalAmount = $vendorBills->sum('bill_amount');
                $paidAmount = $vendorBills->sum(fn (VendorBill $b) => $b->total_paid_amount);
                $outstanding = $vendorBills->sum(fn (VendorBill $b) => $b->outstanding_amount);

                return 'Vendor Bills — Total: LKR '.number_format($totalAmount, 2).
                    ' | Paid: LKR '.number_format($paidAmount, 2).
                    ' | Outstanding: LKR '.number_format($outstanding, 2);
            })
            ->actions([
                Tables\Actions\Action::make('open_vendor_bill')
                    ->label('Payments')
                    ->icon('heroicon-o-banknotes')
                    ->url(fn (VendorBill $record) => route('filament.admin.resources.vendor-bills.view', ['record' => $record]))
                    ->openUrlInNewTab()
                    ->color('primary'),
                Tables\Actions\Action::make('mark_paid')
                    ->label('Pay balance')
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
                        \Filament\Notifications\Notification::make()->success()->title('Balance recorded')->send();
                    })
                    ->visible(fn (VendorBill $record) => $record->outstanding_amount > 0),
                Tables\Actions\EditAction::make(),
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
