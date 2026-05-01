<?php

namespace App\Filament\Resources\SupplierPayablesResource\RelationManagers;

use App\Filament\Resources\VendorBillResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupplierOpenPayablesRelationManager extends RelationManager
{
    protected static string $relationship = 'vendorBills';

    protected static ?string $title = 'What you owe (by lead)';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): void {
                $query
                    ->whereIn('payment_status', ['pending', 'partial'])
                    ->with(['invoice.lead', 'vendorBillPayments'])
                    ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
                    ->orderBy('due_date');
            })
            ->recordTitleAttribute('vendor_bill_number')
            ->columns([
                Tables\Columns\TextColumn::make('lead')
                    ->label('Lead')
                    ->getStateUsing(function ($record): string {
                        $record->loadMissing('invoice.lead');
                        $lead = $record->invoice?->lead;
                        if (! $lead) {
                            return '—';
                        }
                        $ref = $lead->reference_id ?: '#'.$lead->id;

                        return "{$ref} — {$lead->customer_name}";
                    }),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Pay by')
                    ->date()
                    ->placeholder('No due date')
                    ->sortable(),
                Tables\Columns\TextColumn::make('outstanding_amount')
                    ->label('Amount to pay')
                    ->money('LKR')
                    ->getStateUsing(fn ($record): float => $record->outstanding_amount),
                Tables\Columns\TextColumn::make('bill_amount')
                    ->label('Bill total')
                    ->money('LKR')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('vendor_bill_number')
                    ->label('Bill #')
                    ->url(fn ($record): ?string => VendorBillResource::canView($record)
                        ? VendorBillResource::getUrl('view', ['record' => $record])
                        : null),
                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? ucfirst((string) $state) : '—')
                    ->color(fn (?string $state): string => match ($state) {
                        'pending' => 'warning',
                        'partial' => 'info',
                        'paid' => 'success',
                        default => 'gray',
                    }),
            ])
            ->paginated([25, 50, 100]);
    }
}
