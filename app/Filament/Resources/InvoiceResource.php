<?php

namespace App\Filament\Resources;

use App\Enums\QuoteStatus;
use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Quote;
use App\Traits\HasResourcePermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InvoiceResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $label = 'Invoice';

    protected static ?string $pluralLabel = 'Invoices';

    public static function canCreate(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if ($user->canManageAccountingRecords()) {
            return true;
        }

        if ($user->canCreateResource('invoices')) {
            return true;
        }

        // Confirm Lead / lead workflows: same roles that work confirmed leads may create invoices.
        return $user->isSales() || $user->isOperation();
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->canManageAccountingRecords() ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->canManageAccountingRecords() ?? false;
    }

    /**
     * Vendor bills + customer payment receipts on invoice view (header actions + relation tabs).
     */
    public static function canManageInvoiceLinkedFinancialRecords(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if ($user->isAdmin() || $user->canManageAccountingRecords()) {
            return true;
        }

        if ($user->hasPermission('vendor_bills.create') || $user->hasPermission('vendor_bills.edit')) {
            return true;
        }

        if ($user->hasPermission('invoices.view')) {
            return true;
        }

        return static::canCreate();
    }

    public static function canRecordVendorBills(): bool
    {
        return static::canManageInvoiceLinkedFinancialRecords();
    }

    /** @see canManageInvoiceLinkedFinancialRecords() */
    public static function canRecordCustomerPayments(): bool
    {
        return static::canManageInvoiceLinkedFinancialRecords();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();
        if ($user) {
            $query->visibleToUser($user);
        }

        return $query;
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->canViewResource('invoices')) {
            return false;
        }

        return $user->canViewInvoice($record);
    }

    /** Scope lead dropdown on create to leads this user may invoice. */
    public static function leadOptionsQuery(Builder $query): Builder
    {
        $user = auth()->user();
        if (! $user || $user->canViewAllInvoices()) {
            return $query;
        }
        if ($user->isSales()) {
            return $query->where('assigned_to', $user->id);
        }
        if ($user->isOperation()) {
            return $query->where('assigned_operator', $user->id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Invoice Information')
                    ->schema([
                        Forms\Components\Select::make('lead_id')
                            ->label('Lead')
                            ->relationship('lead', 'reference_id', fn (Builder $query): Builder => static::leadOptionsQuery($query))
                            ->getOptionLabelFromRecordUsing(fn (Lead $record): string => "{$record->reference_id} - {$record->customer_name}"
                            )
                            ->searchable(['reference_id', 'customer_name'])
                            ->required()
                            ->disabledOn('edit')
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set): void {
                                if (! $state) {
                                    $set('quote_id', null);
                                    $set('subject', null);
                                    $set('terms', 'Due on Receipt');
                                    $set('notes', null);
                                    $set('due_date', now()->format('Y-m-d'));
                                    $set('lineItems', [
                                        [
                                            'description' => '',
                                            'quantity' => 1,
                                            'rate' => 0,
                                            'customer_details' => null,
                                        ],
                                    ]);

                                    return;
                                }

                                foreach (Quote::invoiceFormStateForLeadId((int) $state) as $key => $value) {
                                    $set($key, $value);
                                }
                            })
                            ->helperText('Invoice number is assigned on save as INV/YEAR/LEAD_ID (e.g. INV/2026/42).'),
                        Forms\Components\Select::make('quote_id')
                            ->label('Load from quote (optional)')
                            ->placeholder('None — add line items below')
                            ->options(function (Get $get): array {
                                $leadId = $get('lead_id');
                                if (! $leadId) {
                                    return [];
                                }

                                return Quote::query()
                                    ->where('lead_id', $leadId)
                                    ->where('status', QuoteStatus::Draft)
                                    ->orderByDesc('id')
                                    ->pluck('quote_number', 'id')
                                    ->all();
                            })
                            ->searchable()
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set): void {
                                if (! $state) {
                                    return;
                                }
                                $quote = Quote::with(['lineItems' => fn ($q) => $q->orderBy('sort_order')])->find($state);
                                if (! $quote) {
                                    return;
                                }
                                foreach ($quote->attributesForInvoiceForm() as $key => $value) {
                                    $set($key, $value);
                                }
                            })
                            ->visible(fn (Get $get): bool => (bool) $get('lead_id'))
                            ->helperText('Prefills line items from a draft quote; edit before saving.')
                            ->visibleOn('create'),
                        Forms\Components\Placeholder::make('invoice_number_preview')
                            ->label('Invoice number (on save)')
                            ->content(fn (Get $get): string => $get('lead_id')
                                ? 'INV/'.now()->year.'/'.$get('lead_id').' (adds -2, -3… if that number is already used).'
                                : 'Select a lead first.')
                            ->visibleOn('create')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('invoice_number')
                            ->label('Invoice Number')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->disabledOn('edit')
                            ->dehydrated()
                            ->hiddenOn('create'),
                        Forms\Components\DatePicker::make('invoice_date')
                            ->label('Invoice date')
                            ->default(now()),
                        Forms\Components\DatePicker::make('due_date')
                            ->label('Due date')
                            ->default(now()),
                        Forms\Components\TextInput::make('terms')
                            ->label('Terms')
                            ->maxLength(255)
                            ->default('Due on Receipt'),
                        Forms\Components\TextInput::make('subject')
                            ->label('Subject')
                            ->maxLength(255),
                        Forms\Components\Hidden::make('total_amount')
                            ->default(0),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->placeholder('Brief description of the invoice')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Line items')
                    ->description('Same pattern as quotes: description, optional customer details, qty and rate. Invoice total is updated when you save.')
                    ->schema([
                        Forms\Components\Repeater::make('lineItems')
                            ->schema([
                                Forms\Components\Hidden::make('lead_cost_id'),
                                Forms\Components\Textarea::make('description')
                                    ->required()
                                    ->rows(2)
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('customer_details')
                                    ->label('Customer details')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->required(),
                                Forms\Components\TextInput::make('rate')
                                    ->label('Rate (LKR)')
                                    ->numeric()
                                    ->prefix('LKR')
                                    ->required(),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                            ->reorderable()
                            ->orderColumn('sort_order')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['description'] ?? null),
                    ]),

                Forms\Components\Section::make('💰 Customer Payment (Money IN)')
                    ->description('Track payments received from customers')
                    ->hiddenOn('create')
                    ->schema([
                        Forms\Components\Select::make('customer_payment_status')
                            ->label('Customer Payment Status')
                            ->options([
                                'pending' => 'Pending Payment',
                                'partial' => 'Partially Paid',
                                'paid' => 'Fully Paid',
                            ])
                            ->default('pending')
                            ->required()
                            ->disabled()
                            ->helperText('Automatically calculated based on payments received'),
                        Forms\Components\Placeholder::make('customer_payment_summary')
                            ->label('Payment Summary')
                            ->content(function ($record) {
                                if (! $record) {
                                    return 'No payments yet - add payments below after saving';
                                }

                                $totalAmount = $record->total_amount;
                                $totalPaid = $record->total_customer_payments_amount;
                                $balance = $record->customer_balance_amount;
                                $paymentCount = $record->customerPayments->count();

                                return 'Total Invoice: LKR '.number_format($totalAmount, 2).
                                       ' | Paid: LKR '.number_format($totalPaid, 2)." ({$paymentCount} payments)".
                                       ' | Balance: LKR '.number_format($balance, 2);
                            })
                            ->helperText('Manage individual payments in the Customer Payments tab below'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('🏪 Vendor Payment (Money OUT)')
                    ->description('Track payments made to vendors')
                    ->hiddenOn('create')
                    ->schema([
                        Forms\Components\Select::make('vendor_payment_status')
                            ->label('Vendor Payment Status')
                            ->options([
                                'pending' => 'Pending Payment',
                                'partial' => 'Partially Paid',
                                'paid' => 'Fully Paid',
                            ])
                            ->default('pending')
                            ->required()
                            ->disabled()
                            ->helperText('Automatically calculated based on vendor bills'),
                        Forms\Components\Placeholder::make('vendor_bills_info')
                            ->label('Vendor Bills Summary')
                            ->content(function ($record) {
                                if (! $record) {
                                    return 'No vendor bills yet';
                                }

                                $totalBills = $record->vendorBills->count();
                                $paidBills = $record->vendorBills->where('payment_status', 'paid')->count();
                                $totalAmount = $record->total_vendor_bills_amount;

                                return "Bills: {$paidBills}/{$totalBills} paid | Total: LKR ".number_format($totalAmount, 2);
                            })
                            ->helperText('Manage individual vendor bills in the Vendor Bills tab below'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Additional Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with([
                'lead',
                'vendorBills',
                'customerPayments',
            ]))
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('lead.reference_id')
                    ->label('Lead #')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('lead', function (Builder $q) use ($search) {
                            $q->where('reference_id', 'like', "%{$search}%")
                                ->orWhere('id', $search)
                                ->orWhere('customer_name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable()
                    ->formatStateUsing(function (?string $state, Invoice $record): ?string {
                        $lead = $record->lead;
                        if (! $lead) {
                            return null;
                        }

                        return filled($lead->reference_id) ? $lead->reference_id : '#'.$lead->id;
                    })
                    ->url(fn ($record) => $record->lead ? route('filament.admin.resources.leads.view', ['record' => $record->lead]) : null)
                    ->color('info'),
                Tables\Columns\TextColumn::make('lead.customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->limit(30),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Invoice Amount')
                    ->money('LKR')
                    ->sortable()
                    ->alignRight()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('total_vendor_bills_amount')
                    ->label('Vendor Bills')
                    ->money('LKR')
                    ->sortable()
                    ->alignRight()
                    ->getStateUsing(fn ($record) => $record->total_vendor_bills_amount),
                Tables\Columns\TextColumn::make('profit')
                    ->label('Profit')
                    ->money('LKR')
                    ->sortable()
                    ->alignRight()
                    ->getStateUsing(fn ($record) => $record->profit)
                    ->color(fn ($state) => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'gray'))
                    ->weight('bold'),
                Tables\Columns\BadgeColumn::make('customer_payment_status')
                    ->label('Customer Payment')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'partial',
                        'success' => 'paid',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Pending',
                        'partial' => 'Partial',
                        'paid' => 'Paid',
                        default => ucfirst($state)
                    }),
                Tables\Columns\BadgeColumn::make('vendor_payment_status')
                    ->label('Vendor Payment')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'partial',
                        'success' => 'paid',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Pending',
                        'partial' => 'Partial',
                        'paid' => 'Paid',
                        default => ucfirst($state)
                    }),
                Tables\Columns\TextColumn::make('total_customer_payments_amount')
                    ->label('Customer Paid')
                    ->money('LKR')
                    ->sortable()
                    ->alignRight()
                    ->getStateUsing(fn ($record) => $record->total_customer_payments_amount)
                    ->placeholder('LKR 0.00'),
                Tables\Columns\TextColumn::make('customer_balance_amount')
                    ->label('Balance Due')
                    ->money('LKR')
                    ->sortable()
                    ->alignRight()
                    ->getStateUsing(fn ($record) => $record->customer_balance_amount)
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'success'),
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Last Payment')
                    ->date('M j, Y')
                    ->sortable()
                    ->placeholder('No payments')
                    ->getStateUsing(fn ($record) => $record->customerPayments()->latest('payment_date')->first()?->payment_date)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->since()
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small)
                    ->color('gray'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('customer_payment_status')
                    ->label('Customer Payment Status')
                    ->options([
                        'pending' => 'Pending',
                        'partial' => 'Partially Paid',
                        'paid' => 'Fully Paid',
                    ]),
                Tables\Filters\SelectFilter::make('vendor_payment_status')
                    ->label('Vendor Payment Status')
                    ->options([
                        'pending' => 'Pending',
                        'partial' => 'Partially Paid',
                        'paid' => 'Fully Paid',
                    ]),
                Tables\Filters\Filter::make('amount_range')
                    ->form([
                        Forms\Components\TextInput::make('amount_from')
                            ->label('Amount From')
                            ->numeric()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('amount_to')
                            ->label('Amount To')
                            ->numeric()
                            ->prefix('$'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['amount_from'],
                                fn (Builder $query, $amount): Builder => $query->where('total_amount', '>=', $amount),
                            )
                            ->when(
                                $data['amount_to'],
                                fn (Builder $query, $amount): Builder => $query->where('total_amount', '<=', $amount),
                            );
                    }),
                Tables\Filters\Filter::make('profitable')
                    ->label('Profitable Only')
                    ->query(fn (Builder $query): Builder => $query->whereHas('vendorBills', function (Builder $query) {
                        $query->selectRaw('invoice_id, SUM(bill_amount) as total_vendor_amount')
                            ->groupBy('invoice_id')
                            ->havingRaw('total_vendor_amount < (SELECT total_amount FROM invoices WHERE invoices.id = vendor_bills.invoice_id)');
                    })),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CustomerPaymentsRelationManager::class,
            RelationManagers\VendorBillsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'view' => Pages\ViewInvoice::route('/{record}'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
