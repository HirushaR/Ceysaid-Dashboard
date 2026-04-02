<?php

namespace App\Filament\Resources\ConfirmLeadResource\RelationManagers;

use App\Services\DocumentNumberService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

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
                            ->dehydrated(),
                        Forms\Components\DatePicker::make('invoice_date')->default(now()),
                        Forms\Components\DatePicker::make('due_date')->default(now()),
                        Forms\Components\TextInput::make('terms')->default('Due on Receipt'),
                        Forms\Components\TextInput::make('subject'),
                        Forms\Components\Hidden::make('total_amount')->default(0),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Line items')
                    ->schema([
                        Forms\Components\Repeater::make('lineItems')
                            ->relationship()
                            ->schema([
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
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->url(fn ($record) => $record ? route('filament.admin.resources.invoices.view', ['record' => $record]) : null)
                    ->color('info'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Amount')
                    ->money('LKR')
                    ->sortable()
                    ->alignRight()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->description),
                Tables\Columns\TextColumn::make('vendorBills')
                    ->label('Vendor bills')
                    ->formatStateUsing(function ($record) {
                        if ($record->vendorBills->isEmpty()) {
                            return '—';
                        }

                        return $record->vendorBills->pluck('vendor_bill_number')->join(', ');
                    })
                    ->limit(30),
                Tables\Columns\TextColumn::make('total_vendor_bills_amount')
                    ->label('Vendor total')
                    ->money('LKR')
                    ->alignRight()
                    ->getStateUsing(fn ($record) => $record->total_vendor_bills_amount),
                Tables\Columns\BadgeColumn::make('customer_payment_status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'partial',
                        'success' => 'paid',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst((string) $state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer_payment_status')
                    ->options([
                        'pending' => 'Pending',
                        'partial' => 'Partial',
                        'paid' => 'Paid',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Create invoice')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['lead_id'] = $this->getOwnerRecord()->id;
                        $data['invoice_number'] = app(DocumentNumberService::class)->nextInvoiceNumber();
                        $data['total_amount'] = $data['total_amount'] ?? 0;

                        return $data;
                    })
                    ->after(function ($record) {
                        $record->recalculateTotalsFromLineItems();
                    }),
            ])
            ->heading(function () {
                $owner = $this->getOwnerRecord();
                $invoices = $owner->invoices;
                $total = $invoices->sum('total_amount');
                $paid = $invoices->where('customer_payment_status', 'paid')->sum('total_amount');

                return 'Invoices — Total: LKR '.number_format($total, 2).' | Paid: LKR '.number_format($paid, 2);
            })
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(function ($record) {
                        $record->recalculateTotalsFromLineItems();
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn ($record) => $record ? route('filament.admin.resources.invoices.view', ['record' => $record]) : null);
    }
}
