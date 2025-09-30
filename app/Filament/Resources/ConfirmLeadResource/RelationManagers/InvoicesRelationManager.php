<?php

namespace App\Filament\Resources\ConfirmLeadResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Invoice;
use App\Models\VendorBill;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    protected static ?string $recordTitleAttribute = 'invoice_number';

    public function canCreate(): bool
    {
        return true;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Invoice Information')
                    ->schema([
                        Forms\Components\TextInput::make('invoice_number')
                            ->label('Invoice Number')
                            ->required()
                            ->unique('invoices', 'invoice_number', ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('e.g., INV20252345'),
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total Amount')
                            ->required()
                            ->numeric()
                            ->step(0.01)
                            ->prefix('$'),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->placeholder('Brief description of the invoice')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Payment Information')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Payment Status')
                            ->options([
                                'pending' => 'Pending',
                                'partial' => 'Partially Paid',
                                'paid' => 'Fully Paid',
                            ])
                            ->default('pending')
                            ->required()
                            ->live(),
                        Forms\Components\TextInput::make('payment_amount')
                            ->label('Payment Amount')
                            ->numeric()
                            ->step(0.01)
                            ->prefix('$')
                            ->visible(fn($get) => in_array($get('status'), ['partial', 'paid'])),
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Payment Date')
                            ->visible(fn($get) => in_array($get('status'), ['partial', 'paid'])),
                        Forms\Components\TextInput::make('receipt_number')
                            ->label('Receipt Number')
                            ->maxLength(255)
                            ->placeholder('e.g., RC202534')
                            ->visible(fn($get) => in_array($get('status'), ['partial', 'paid'])),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Vendor Bills')
                    ->schema([
                        Forms\Components\Repeater::make('vendorBills')
                            ->relationship('vendorBills')
                            ->schema([
                                Forms\Components\TextInput::make('vendor_name')
                                    ->label('Vendor Name')
                                    ->required()
                                    ->placeholder('e.g., IATA, TRAVEL BUDDY'),
                                Forms\Components\TextInput::make('vendor_bill_number')
                                    ->label('Vendor Bill Number')
                                    ->required()
                                    ->placeholder('e.g., XO20252345'),
                                Forms\Components\TextInput::make('bill_amount')
                                    ->label('Amount')
                                    ->required()
                                    ->numeric()
                                    ->step(0.01)
                                    ->prefix('$'),
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
                                    ->required(),
                                Forms\Components\Textarea::make('service_details')
                                    ->label('Details')
                                    ->rows(2)
                                    ->columnSpanFull(),
                                Forms\Components\Select::make('payment_status')
                                    ->label('Payment Status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'paid' => 'Paid',
                                    ])
                                    ->default('pending')
                                    ->required(),
                            ])
                            ->createItemButtonLabel('Add Vendor Bill')
                            ->reorderable(false)
                            ->columns(2)
                            ->collapsible()
                            ->cloneable(),
                    ])
                    ->collapsible(),

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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('invoice_number')
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice Number')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('medium')
                    ->url(fn($record) => $record ? route('filament.admin.resources.invoices.view', ['record' => $record]) : null)
                    ->color('info'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Amount')
                    ->money('LKR')
                    ->sortable()
                    ->alignRight()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('description')
                    ->label('Details')
                    ->limit(50)
                    ->tooltip(function ($record) {
                        return $record->description;
                    })
                    ->placeholder('No details'),
                Tables\Columns\TextColumn::make('vendorBills')
                    ->label('Vendor Bills')
                    ->formatStateUsing(function ($record) {
                        $vendorBills = $record->vendorBills;
                        if ($vendorBills->isEmpty()) {
                            return 'No vendor bills';
                        }
                        
                        return $vendorBills->map(function ($bill) {
                            return $bill->vendor_bill_number;
                        })->join(', ');
                    })
                    ->limit(30)
                    ->tooltip(function ($record) {
                        $vendorBills = $record->vendorBills;
                        if ($vendorBills->isEmpty()) {
                            return 'No vendor bills attached';
                        }
                        
                        return $vendorBills->map(function ($bill) {
                            return "{$bill->vendor_name}: {$bill->vendor_bill_number} (LKR {$bill->bill_amount})";
                        })->join("\n");
                    }),
                Tables\Columns\TextColumn::make('total_vendor_bills_amount')
                    ->label('Vendor Amount')
                    ->money('LKR')
                    ->sortable()
                    ->alignRight()
                    ->getStateUsing(function ($record) {
                        return $record->total_vendor_bills_amount;
                    }),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'partial',
                        'success' => 'paid',
                    ])
                    ->formatStateUsing(fn($state) => ucfirst($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Payment Status')
                    ->options([
                        'pending' => 'Pending',
                        'partial' => 'Partially Paid',
                        'paid' => 'Fully Paid',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Create Invoice')
                    ->modalHeading('Create Invoice')
                    ->modalButton('Create Invoice')
                    ->color('primary')
                    ->icon('heroicon-o-plus')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['lead_id'] = $this->getOwnerRecord()->id;
                        return $data;
                    })
                    ->successNotificationTitle('Invoice created successfully'),
            ])
            ->heading(function () {
                $ownerRecord = $this->getOwnerRecord();
                $invoices = $ownerRecord->invoices;
                $totalInvoiceAmount = $invoices->sum('total_amount');
                $paidInvoiceAmount = $invoices->where('customer_payment_status', 'paid')->sum('total_amount');
                $unpaidInvoiceAmount = $totalInvoiceAmount - $paidInvoiceAmount;
                $paidCount = $invoices->where('customer_payment_status', 'paid')->count();
                $unpaidCount = $invoices->whereIn('customer_payment_status', ['pending', 'partial'])->count();
                
                return "Invoices - Total: LKR " . number_format($totalInvoiceAmount, 2) . 
                       " | Paid: LKR " . number_format($paidInvoiceAmount, 2) . " ({$paidCount})" .
                       " | Unpaid: LKR " . number_format($unpaidInvoiceAmount, 2) . " ({$unpaidCount})";
            })
            ->actions([
                Tables\Actions\Action::make('manage_vendor_bills')
                    ->label('Vendor Bills')
                    ->icon('heroicon-o-receipt-percent')
                    ->color('info')
                    ->url(fn ($record) => $record ? route('filament.admin.resources.invoices.view', ['record' => $record]) : null)
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn($record) => $record ? route('filament.admin.resources.invoices.view', ['record' => $record]) : null);
    }
}
