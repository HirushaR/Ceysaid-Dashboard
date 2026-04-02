<?php

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Enums\QuoteStatus;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\QuoteResource;
use App\Services\ConvertQuoteToInvoiceService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewQuote extends ViewRecord
{
    protected static string $resource = QuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn () => route('finance.quotes.pdf', ['quote' => $this->record]))
                ->openUrlInNewTab(),

            Action::make('convert_to_invoice')
                ->label('Convert to invoice')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => (auth()->user()?->canManageAccountingRecords() ?? false)
                    && $this->record->status === QuoteStatus::Draft)
                ->action(function (ConvertQuoteToInvoiceService $service) {
                    try {
                        $invoice = $service->convert($this->record);
                        Notification::make()->success()->title('Invoice created')->send();

                        return redirect(InvoiceResource::getUrl('view', ['record' => $invoice]));
                    } catch (\Throwable $e) {
                        Notification::make()->danger()->title($e->getMessage())->send();
                    }
                }),
            EditAction::make()
                ->visible(fn () => $this->record->status === QuoteStatus::Draft),
        ];
    }
}
