<?php

namespace App\Filament\Resources\SupplierPayablesResource\RelationManagers;

use App\Enums\DepositAccount;
use App\Enums\PaymentMode;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SupplierPaymentHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'vendorBillPayments';

    protected static ?string $title = 'Payment history';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lead')
                    ->label('Lead')
                    ->getStateUsing(function ($record): string {
                        $record->loadMissing('vendorBill.invoice.lead');
                        $lead = $record->vendorBill?->invoice?->lead;
                        if (! $lead) {
                            return '—';
                        }
                        $ref = $lead->reference_id ?: '#'.$lead->id;

                        return "{$ref} — {$lead->customer_name}";
                    }),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('LKR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_through')
                    ->label('Bank / account')
                    ->formatStateUsing(fn (?string $state): string => $state
                        ? (DepositAccount::tryFrom($state)?->label() ?? ucfirst(str_replace('_', ' ', $state)))
                        : '—'),
                Tables\Columns\TextColumn::make('payment_mode')
                    ->label('Mode')
                    ->formatStateUsing(fn (?string $state): string => $state
                        ? (PaymentMode::tryFrom($state)?->label() ?? $state)
                        : '—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('vendorBill.vendor_bill_number')
                    ->label('Bill #')
                    ->placeholder('—'),
            ])
            ->defaultSort('payment_date', 'desc')
            ->paginated([25, 50, 100]);
    }
}
