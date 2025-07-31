<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VendorBillsRelationManager extends RelationManager
{
    protected static string $relationship = 'vendorBills';

    protected static ?string $recordTitleAttribute = 'vendor_name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('vendor_name')
                    ->label('Vendor Name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g., IATA, TRAVEL BUDDY, MALAYSIA E VISA'),
                Forms\Components\TextInput::make('vendor_bill_number')
                    ->label('Vendor Bill Number')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g., XO20252345'),
                Forms\Components\TextInput::make('bill_amount')
                    ->label('Bill Amount')
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
                    ->required()
                    ->searchable(),
                Forms\Components\Textarea::make('service_details')
                    ->label('Service Details')
                    ->rows(3)
                    ->placeholder('Additional details about the service')
                    ->columnSpanFull(),
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
                    ->visible(fn($get) => $get('payment_status') === 'paid'),
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
            ->columns([
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
                    ->money('USD')
                    ->sortable()
                    ->alignRight()
                    ->weight('bold'),
                Tables\Columns\BadgeColumn::make('payment_status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'paid',
                    ])
                    ->formatStateUsing(fn($state) => ucfirst($state)),
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
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
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
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->modalHeading('Add Vendor Bill')
                    ->modalButton('Add Bill'),
            ])
            ->heading(function () {
                $ownerRecord = $this->getOwnerRecord();
                $vendorBills = $ownerRecord->vendorBills;
                $totalAmount = $vendorBills->sum('bill_amount');
                $paidAmount = $vendorBills->where('payment_status', 'paid')->sum('bill_amount');
                $unpaidAmount = $vendorBills->where('payment_status', 'pending')->sum('bill_amount');
                $paidCount = $vendorBills->where('payment_status', 'paid')->count();
                $unpaidCount = $vendorBills->where('payment_status', 'pending')->count();
                
                return "Vendor Bills - Total: $" . number_format($totalAmount, 2) . 
                       " | Paid: $" . number_format($paidAmount, 2) . " ({$paidCount})" .
                       " | Unpaid: $" . number_format($unpaidAmount, 2) . " ({$unpaidCount})";
            })
            ->actions([
                Tables\Actions\Action::make('mark_paid')
                    ->label('Mark Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Mark as Paid')
                    ->modalDescription('Are you sure you want to mark this vendor bill as paid?')
                    ->action(function ($record) {
                        $record->markAsPaid();
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Vendor bill marked as paid')
                            ->body("Bill {$record->vendor_bill_number} has been marked as paid.")
                            ->send();
                    })
                    ->visible(fn ($record) => $record->isPending()),
                Tables\Actions\Action::make('mark_pending')
                    ->label('Mark Pending')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Mark as Pending')
                    ->modalDescription('Are you sure you want to mark this vendor bill as pending?')
                    ->action(function ($record) {
                        $record->markAsPending();
                        \Filament\Notifications\Notification::make()
                            ->warning()
                            ->title('Vendor bill marked as pending')
                            ->body("Bill {$record->vendor_bill_number} has been marked as pending.")
                            ->send();
                    })
                    ->visible(fn ($record) => $record->isPaid()),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_paid_bulk')
                        ->label('Mark as Paid')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Mark Selected Bills as Paid')
                        ->modalDescription('Are you sure you want to mark the selected vendor bills as paid?')
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each(function ($record) {
                                $record->markAsPaid();
                            });
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Vendor bills marked as paid')
                                ->body("{$count} vendor bill(s) have been marked as paid.")
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('mark_pending_bulk')
                        ->label('Mark as Pending')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Mark Selected Bills as Pending')
                        ->modalDescription('Are you sure you want to mark the selected vendor bills as pending?')
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each(function ($record) {
                                $record->markAsPending();
                            });
                            \Filament\Notifications\Notification::make()
                                ->warning()
                                ->title('Vendor bills marked as pending')
                                ->body("{$count} vendor bill(s) have been marked as pending.")
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
