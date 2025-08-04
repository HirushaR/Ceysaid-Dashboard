<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RevenueProfitTrendWidget extends ChartWidget
{
    public function getHeading(): string
    {
        return 'Revenue & Profit Trends';
    }

    protected static ?string $maxHeight = '300px';
    protected static ?string $pollingInterval = null;

    protected function getData(): array
    {
        $user = auth()->user();
        
        // Get monthly data for the last 12 months
        $months = [];
        $revenueData = [];
        $profitData = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months[] = $date->format('M Y');
            
            // Get revenue for this month
            $revenue = Invoice::whereHas('lead', function($query) use ($user, $date) {
                $query->where('assigned_to', $user ? $user->id : null)
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month);
            })->sum('total_amount');
            
            $revenueData[] = $revenue;
            
            // Get profit for this month
            $profit = Invoice::whereHas('lead', function($query) use ($user, $date) {
                $query->where('assigned_to', $user ? $user->id : null)
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month);
            })->get()->sum('profit');
            
            $profitData[] = $profit;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $revenueData,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Profit',
                    'data' => $profitData,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            return context.dataset.label + ": $" + context.parsed.y.toLocaleString("en-US", {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        }',
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) {
                            return "$" + value.toLocaleString("en-US", {minimumFractionDigits: 0, maximumFractionDigits: 0});
                        }',
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
} 