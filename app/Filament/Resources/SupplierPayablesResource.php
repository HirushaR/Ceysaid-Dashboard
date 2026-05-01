<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierPayablesResource\Pages;
use App\Filament\Resources\SupplierPayablesResource\RelationManagers;
use App\Models\Supplier;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SupplierPayablesResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $slug = 'supplier-payables';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Supplier payables';

    protected static ?string $label = 'Supplier payable';

    protected static ?string $pluralLabel = 'Supplier payables';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['vendorBills.vendorBillPayments'])
            ->select('suppliers.*')
            ->selectSub(
                DB::table('vendor_bills as vb')
                    ->selectRaw('COALESCE(SUM(CASE WHEN (vb.bill_amount - COALESCE((SELECT SUM(vbp.amount) FROM vendor_bill_payments vbp WHERE vbp.vendor_bill_id = vb.id), 0)) < 0 THEN 0 ELSE (vb.bill_amount - COALESCE((SELECT SUM(vbp.amount) FROM vendor_bill_payments vbp WHERE vbp.vendor_bill_id = vb.id), 0)) END), 0)')
                    ->whereColumn('vb.supplier_id', 'suppliers.id'),
                'payable_amount'
            )
            ->orderByDesc('payable_amount')
            ->orderBy('suppliers.name');
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user && ($user->isAdmin() || $user->isAccount());
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('contact_name')
                    ->label('Contact')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('payable_amount')
                    ->label('Total to pay')
                    ->money('LKR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('vendor_bills_count')
                    ->counts('vendorBills')
                    ->label('Bills')
                    ->sortable(),
            ])
            ->defaultSort('payable_amount', 'desc')
            ->recordUrl(fn (Supplier $record): string => static::getUrl('view', ['record' => $record]))
            ->recordTitleAttribute('name')
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupplierPayables::route('/'),
            'view' => Pages\ViewSupplierPayable::route('/{record}'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SupplierOpenPayablesRelationManager::class,
            RelationManagers\SupplierPaymentHistoryRelationManager::class,
        ];
    }
}
