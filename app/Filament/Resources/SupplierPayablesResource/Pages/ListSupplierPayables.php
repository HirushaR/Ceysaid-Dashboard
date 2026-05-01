<?php

namespace App\Filament\Resources\SupplierPayablesResource\Pages;

use App\Filament\Resources\SupplierPayablesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSupplierPayables extends ListRecords
{
    protected static string $resource = SupplierPayablesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download_summary_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn (): string => route('finance.supplier-payables.summary-pdf'))
                ->openUrlInNewTab(),
        ];
    }
}
