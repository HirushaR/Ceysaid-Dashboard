<?php

namespace App\Filament\Resources\SupplierPayablesResource\Pages;

use App\Filament\Resources\SupplierPayablesResource;
use App\Filament\Resources\SupplierResource;
use Filament\Actions;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewSupplierPayable extends ViewRecord
{
    protected static string $resource = SupplierPayablesResource::class;

    protected function resolveRecord($key): \Illuminate\Database\Eloquent\Model
    {
        return static::getResource()::getEloquentQuery()->findOrFail($key);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn (): string => route('finance.supplier-payables.pdf', ['supplier' => $this->record]))
                ->openUrlInNewTab(),
            Actions\Action::make('open_supplier')
                ->label('Supplier profile')
                ->icon('heroicon-o-building-storefront')
                ->url(fn (): string => SupplierResource::getUrl('view', ['record' => $this->record]))
                ->visible(fn (): bool => SupplierResource::canView($this->record)),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Supplier')
                    ->schema([
                        Components\TextEntry::make('name')
                            ->label('Name'),
                        Components\TextEntry::make('contact_name')
                            ->label('Contact')
                            ->placeholder('—'),
                        Components\TextEntry::make('phone')
                            ->placeholder('—'),
                        Components\TextEntry::make('email')
                            ->placeholder('—'),
                        Components\TextEntry::make('bank_details')
                            ->label('Bank details')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Components\Section::make('Payables summary')
                    ->schema([
                        Components\TextEntry::make('total_to_pay')
                            ->label('Total still to pay')
                            ->money('LKR')
                            ->state(fn ($record) => $record->totalOutstandingPayable()),
                        Components\TextEntry::make('vendor_bills_count')
                            ->label('Vendor bills')
                            ->state(fn ($record) => $record->vendorBills()->count()),
                    ])
                    ->columns(2),
            ]);
    }
}
