<?php

namespace App\Filament\Resources\SalesStaffPerformanceResource\Pages;

use App\Filament\Resources\SalesStaffPerformanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSalesStaffPerformance extends ListRecords
{
    protected static string $resource = SalesStaffPerformanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export')
                ->label('Export Performance Report')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action('exportPerformanceReport'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Sales Staff'),
            
            'top_performers' => Tab::make('Top Performers')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('leads', function($q) {
                    $q->whereIn('status', ['confirmed', 'operation_complete', 'document_upload_complete']);
                })->orderBy('name', 'asc')),
            
            'needs_attention' => Tab::make('Needs Attention')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('leads', function($q) {
                    $q->whereIn('status', ['new', 'assigned_to_sales', 'pricing_in_progress']);
                })),
        ];
    }

    public function exportPerformanceReport(): void
    {
        // This would implement CSV/Excel export functionality
        \Filament\Notifications\Notification::make()
            ->title('Export Started')
            ->body('Performance report export has been initiated.')
            ->success()
            ->send();
    }
}
