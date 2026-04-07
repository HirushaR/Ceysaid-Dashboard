<?php

namespace App\Filament\Resources;

use App\Enums\DepositAccount;
use App\Enums\PaymentMode;
use App\Filament\Resources\VendorBillResource\Pages;
use App\Models\Invoice;
use App\Models\Supplier;
use App\Models\VendorBill;
use App\Traits\HasResourcePermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class VendorBillResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = VendorBill::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $label = 'Vendor Bill';

    protected static ?string $pluralLabel = 'Vendor Bills';

    public static function canCreate(): bool
    {
        return auth()->user()?->canManageAccountingRecords() ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->canManageAccountingRecords() ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->canManageAccountingRecords() ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();
        if ($user && ! $user->canViewAllInvoices() && ($user->isSales() || $user->isOperation())) {
            $query->whereHas('invoice', fn (Builder $q) => $q->visibleToUser($user));
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

        if (! $user->canViewResource('vendor_bills')) {
            return false;
        }

        $record->loadMissing('invoice');

        return $user->canViewInvoice($record->invoice);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Vendor Bill Information')
                    ->schema([
                        Forms\Components\Select::make('invoice_id')
                            ->label('Invoice')
                            ->relationship('invoice', 'invoice_number', function (Builder $query): Builder {
                                $user = auth()->user();
                                if (! $user) {
                                    return $query;
                                }

                                return $query->visibleToUser($user);
                            })
                            ->getOptionLabelFromRecordUsing(fn (Invoice $record): string => "{$record->invoice_number} - {$record->lead->customer_name} (LKR {$record->total_amount})"
                            )
                            ->searchable(['invoice_number'])
                            ->required()
                            ->disabledOn('edit'),
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
                            ->label('Vendor Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('vendor_bill_number')
                            ->label('Vendor Bill Number')
                            ->required()
                            ->maxLength(255)
                            ->disabledOn('edit')
                            ->dehydrated(),
                        Forms\Components\TextInput::make('bill_amount')
                            ->label('Bill Amount')
                            ->required()
                            ->numeric()
                            ->step(0.01)
                            ->prefix('LKR'),
                        Forms\Components\DatePicker::make('due_date')
                            ->label('Due date')
                            ->nullable()
                            ->native(false),
                        Forms\Components\Select::make('service_type')
                            ->label('Service Type')
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
                            ->label('Service Details')
                            ->rows(3)
                            ->placeholder('Additional details about the service')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Vendor payment')
                    ->schema([
                        Forms\Components\Select::make('payment_status')
                            ->label('Payment Status')
                            ->options([
                                'pending' => 'Pending',
                                'paid' => 'Paid',
                            ])
                            ->default('pending')
                            ->required()
                            ->live(),
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Payment Date')
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
                    ])
                    ->columns(2),

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
                'invoice.lead',
                'invoice',
                'supplier',
            ]))
            ->columns([
                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->url(fn ($record) => $record->invoice ? route('filament.admin.resources.invoices.view', ['record' => $record->invoice]) : null)
                    ->color('info'),
                Tables\Columns\TextColumn::make('invoice.lead.reference_id')
                    ->label('Lead #')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('invoice.lead', function (Builder $q) use ($search) {
                            $q->where('reference_id', 'like', "%{$search}%")
                                ->orWhere('id', $search)
                                ->orWhere('customer_name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable()
                    ->formatStateUsing(function (?string $state, VendorBill $record): ?string {
                        $lead = $record->invoice?->lead;
                        if (! $lead) {
                            return null;
                        }

                        return filled($lead->reference_id) ? $lead->reference_id : '#'.$lead->id;
                    })
                    ->url(fn ($record) => $record->invoice && $record->invoice->lead ? route('filament.admin.resources.leads.view', ['record' => $record->invoice->lead]) : null)
                    ->color('primary'),
                Tables\Columns\TextColumn::make('invoice.lead.customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->limit(25),
                Tables\Columns\TextColumn::make('vendor_name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('vendor_bill_number')
                    ->label('Bill Number')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('service_type')
                    ->label('Service')
                    ->badge()
                    ->colors([
                        'info' => 'AIR TICKET',
                        'success' => 'HOTEL',
                        'warning' => 'VISA',
                        'primary' => 'LAND PACKAGE',
                        'gray' => 'OTHER',
                    ]),
                Tables\Columns\TextColumn::make('bill_amount')
                    ->label('Amount')
                    ->money('LKR')
                    ->sortable()
                    ->alignRight()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due date')
                    ->date('M j, Y')
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\BadgeColumn::make('payment_status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'paid',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state)),
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Payment Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->placeholder('Not paid'),
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
                Tables\Filters\SelectFilter::make('service_type')
                    ->label('Service Type')
                    ->options([
                        'AIR TICKET' => 'Air Ticket',
                        'HOTEL' => 'Hotel',
                        'VISA' => 'Visa',
                        'LAND PACKAGE' => 'Land Package',
                        'INSURANCE' => 'Insurance',
                        'OTHER' => 'Other',
                    ]),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                    ]),
                Tables\Filters\SelectFilter::make('vendor_name')
                    ->label('Vendor')
                    ->options(function () {
                        return VendorBill::distinct('vendor_name')
                            ->pluck('vendor_name', 'vendor_name')
                            ->toArray();
                    })
                    ->searchable(),
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
                                fn (Builder $query, $amount): Builder => $query->where('bill_amount', '>=', $amount),
                            )
                            ->when(
                                $data['amount_to'],
                                fn (Builder $query, $amount): Builder => $query->where('bill_amount', '<=', $amount),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('mark_paid')
                    ->label('Mark paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\DatePicker::make('payment_date')
                            ->required()
                            ->default(now()),
                        Forms\Components\Select::make('payment_mode')
                            ->label('Payment mode')
                            ->options(PaymentMode::options())
                            ->required(),
                        Forms\Components\Select::make('paid_through')
                            ->label('Paid through')
                            ->options(DepositAccount::options())
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->markAsPaid(
                            $data['payment_date'] ?? null,
                            $data['payment_mode'] ?? null,
                            $data['paid_through'] ?? null
                        );
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Vendor bill marked as paid')
                            ->send();
                    })
                    ->visible(fn ($record) => $record->isPending()),
                Tables\Actions\Action::make('mark_pending')
                    ->label('Mark Pending')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->markAsPending();
                        \Filament\Notifications\Notification::make()
                            ->warning()
                            ->title('Vendor bill marked as pending')
                            ->send();
                    })
                    ->visible(fn ($record) => $record->isPaid()),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_paid_bulk')
                        ->label('Mark as paid')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->form([
                            Forms\Components\DatePicker::make('payment_date')
                                ->required()
                                ->default(now()),
                            Forms\Components\Select::make('payment_mode')
                                ->options(PaymentMode::options())
                                ->required(),
                            Forms\Components\Select::make('paid_through')
                                ->options(DepositAccount::options())
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            $count = $records->count();
                            $records->each(function ($record) use ($data) {
                                $record->markAsPaid(
                                    $data['payment_date'] ?? null,
                                    $data['payment_mode'] ?? null,
                                    $data['paid_through'] ?? null
                                );
                            });
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title("Marked {$count} vendor bills as paid")
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendorBills::route('/'),
            'create' => Pages\CreateVendorBill::route('/create'),
            'view' => Pages\ViewVendorBill::route('/{record}'),
            'edit' => Pages\EditVendorBill::route('/{record}/edit'),
        ];
    }
}
