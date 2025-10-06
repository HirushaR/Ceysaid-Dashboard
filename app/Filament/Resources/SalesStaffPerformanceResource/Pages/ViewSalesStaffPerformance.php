<?php

namespace App\Filament\Resources\SalesStaffPerformanceResource\Pages;

use App\Filament\Resources\SalesStaffPerformanceResource;
use App\Models\Lead;
use App\Models\Invoice;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Grid;
use Illuminate\Support\HtmlString;
use Carbon\Carbon;

class ViewSalesStaffPerformance extends ViewRecord
{
    protected static string $resource = SalesStaffPerformanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh')
                ->label('Refresh Data')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action('refreshData'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Sales Staff Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Name')
                                    ->weight('bold'),
                                
                                TextEntry::make('email')
                                    ->label('Email'),
                                
                                TextEntry::make('role')
                                    ->label('Role')
                                    ->badge()
                                    ->color('primary'),
                                
                                TextEntry::make('created_at')
                                    ->label('Joined Date')
                                    ->dateTime(),
                            ]),
                    ]),
                
                Section::make('Performance Overview')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('total_leads')
                                    ->label('Total Leads')
                                    ->numeric()
                                    ->getStateUsing(function ($record) {
                                        return $record->leads()->count();
                                    }),
                                
                                TextEntry::make('converted_leads')
                                    ->label('Converted Leads')
                                    ->label(fn () => new \Illuminate\Support\HtmlString('Converted Leads <span class="text-xs text-gray-500 cursor-help" data-tooltip="Leads that reached confirmed, operation complete, or document upload complete status">ⓘ</span>'))
                                    ->tooltip('Leads that reached confirmed, operation complete, or document upload complete status')
                                    ->numeric()
                                    ->color('success')
                                    ->getStateUsing(function ($record) {
                                        return $record->leads()
                                            ->whereIn('status', ['confirmed', 'operation_complete', 'document_upload_complete'])
                                            ->count();
                                    }),
                                
                                TextEntry::make('conversion_rate')
                                    ->label('Conversion Rate')
                                    ->label(fn () => new \Illuminate\Support\HtmlString('Conversion Rate <span class="text-xs text-gray-500 cursor-help" data-tooltip="Percentage of total leads that were successfully converted (≥20% = Excellent, 10-19% = Good, <10% = Needs Improvement)">ⓘ</span>'))
                                    ->tooltip('Percentage of total leads that were successfully converted (≥20% = Excellent, 10-19% = Good, <10% = Needs Improvement)')
                                    ->formatStateUsing(fn ($state) => $state . '%')
                                    ->color(fn ($state) => $state >= 20 ? 'success' : ($state >= 10 ? 'warning' : 'danger'))
                                    ->getStateUsing(function ($record) {
                                        $totalLeads = $record->leads()->count();
                                        $convertedLeads = $record->leads()
                                            ->whereIn('status', ['confirmed', 'operation_complete', 'document_upload_complete'])
                                            ->count();
                                        
                                        return $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 1) : 0;
                                    }),
                                
                                TextEntry::make('total_revenue')
                                    ->label('Total Revenue')
                                    ->formatStateUsing(fn ($state) => 'LKR ' . number_format($state, 0, ',', '.'))
                                    ->color('success')
                                    ->getStateUsing(function ($record) {
                                        return Invoice::whereHas('lead', function($query) use ($record) {
                                            $query->where('assigned_to', $record->id);
                                        })->sum('total_amount');
                                    }),
                            ]),
                    ]),
                
                Section::make('Current Status')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('active_leads')
                                    ->label('Active Leads')
                                    ->numeric()
                                    ->color('warning')
                                    ->getStateUsing(function ($record) {
                                        return $record->leads()
                                            ->whereNotIn('status', ['mark_closed', 'operation_complete', 'document_upload_complete'])
                                            ->count();
                                    }),
                                
                                TextEntry::make('pending_leads')
                                    ->label('Pending Leads')
                                    ->numeric()
                                    ->color('danger')
                                    ->getStateUsing(function ($record) {
                                        return $record->leads()
                                            ->whereIn('status', ['new', 'assigned_to_sales', 'pricing_in_progress'])
                                            ->count();
                                    }),
                                
                                TextEntry::make('this_month_revenue')
                                    ->label('This Month Revenue')
                                    ->formatStateUsing(fn ($state) => 'LKR ' . number_format($state, 0, ',', '.'))
                                    ->color('info')
                                    ->getStateUsing(function ($record) {
                                        $now = now();
                                        return Invoice::whereHas('lead', function($query) use ($record) {
                                            $query->where('assigned_to', $record->id);
                                        })->whereYear('created_at', $now->year)
                                          ->whereMonth('created_at', $now->month)
                                          ->sum('total_amount');
                                    }),
                            ]),
                    ]),
                
                Section::make('Recent Leads')
                    ->schema([
                        RepeatableEntry::make('recent_leads')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        TextEntry::make('customer_name')
                                            ->label('Customer'),
                                        
                                        TextEntry::make('platform')
                                            ->label('Source')
                                            ->badge(),
                                        
                                        TextEntry::make('status')
                                            ->label('Status')
                                            ->badge()
                                            ->color(fn ($state) => match($state) {
                                                'confirmed', 'operation_complete', 'document_upload_complete' => 'success',
                                                'new', 'assigned_to_sales' => 'warning',
                                                'mark_closed' => 'danger',
                                                default => 'gray',
                                            }),
                                        
                                        TextEntry::make('created_at')
                                            ->label('Created')
                                            ->dateTime(),
                                    ]),
                            ])
                            ->getStateUsing(function ($record) {
                                return $record->leads()
                                    ->orderBy('created_at', 'desc')
                                    ->limit(10)
                                    ->get()
                                    ->toArray();
                            }),
                    ]),
            ]);
    }

    public function refreshData(): void
    {
        \Filament\Notifications\Notification::make()
            ->title('Data Refreshed')
            ->body('Performance data has been updated.')
            ->success()
            ->send();
    }
}
