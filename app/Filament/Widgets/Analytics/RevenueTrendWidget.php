<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\Invoice;
use App\Services\DateRangeService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RevenueTrendWidget extends ChartWidget
{
    protected static ?string $heading = 'Revenue Trend';
    protected static ?int $sort = 2;
    protected static ?string $pollingInterval = '30s';
    protected static bool $isLazy = true;

    protected function getData(): array
    {
        $analyticsPage = $this->getAnalyticsPage();
        $dateRange = $analyticsPage->getDateRange();
        $filters = $analyticsPage->getFilters();
        
        $cacheKey = 'revenue_trend_' . $analyticsPage->getCacheKey();
        
        return Cache::remember($cacheKey, 300, function () use ($dateRange, $filters) {
            $interval = $dateRange->getInterval();
            $startDate = $dateRange->getStartDate();
            $endDate = $dateRange->getEndDate();

            $query = Invoice::query()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('customer_payment_status', 'paid');

            $this->applyFilters($query, $filters);

            $data = $this->getGroupedData($query, $interval, $startDate, $endDate);

            return [
                'datasets' => [
                    [
                        'label' => 'Revenue ($)',
                        'data' => array_values($data),
                        'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                        'borderColor' => 'rgb(34, 197, 94)',
                        'borderWidth' => 2,
                        'fill' => true,
                    ],
                ],
                'labels' => array_keys($data),
            ];
        });
    }

    protected function getGroupedData($query, string $interval, $startDate, $endDate): array
    {
        $format = match ($interval) {
            'daily' => '%Y-%m-%d',
            'weekly' => '%Y-%u',
            'monthly' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $results = $query
            ->select(
                DB::raw("DATE_FORMAT(created_at, '{$format}') as period"),
                DB::raw('SUM(total_amount) as total')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->pluck('total', 'period')
            ->toArray();

        // Fill missing periods with 0
        $periods = $this->generatePeriods($interval, $startDate, $endDate);
        $data = [];

        foreach ($periods as $period) {
            $key = $this->formatPeriod($period, $interval);
            $data[$key] = $results[$key] ?? 0;
        }

        return $data;
    }

    protected function generatePeriods(string $interval, $startDate, $endDate): array
    {
        $periods = [];
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            $periods[] = $current->copy();
            
            match ($interval) {
                'daily' => $current->addDay(),
                'weekly' => $current->addWeek(),
                'monthly' => $current->addMonth(),
                default => $current->addDay(),
            };
        }

        return $periods;
    }

    protected function formatPeriod($date, string $interval): string
    {
        return match ($interval) {
            'daily' => $date->format('Y-m-d'),
            'weekly' => $date->format('Y-W'),
            'monthly' => $date->format('Y-m'),
            default => $date->format('Y-m-d'),
        };
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'callbacks' => [
                        'label' => 'function(context) {
                            return context.dataset.label + ": $" + context.parsed.y.toLocaleString();
                        }',
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'display' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Period',
                    ],
                ],
                'y' => [
                    'display' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Revenue ($)',
                    ],
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) {
                            return "$" + value.toLocaleString();
                        }',
                    ],
                ],
            ],
        ];
    }

    protected function applyFilters($query, array $filters): void
    {
        if (isset($filters['sales_user'])) {
            $query->whereHas('lead', function ($q) use ($filters) {
                $q->where('assigned_to', $filters['sales_user']);
            });
        }

        if (isset($filters['lead_source'])) {
            $query->whereHas('lead', function ($q) use ($filters) {
                $q->where('platform', $filters['lead_source']);
            });
        }

        if (isset($filters['pipeline_stage'])) {
            $query->whereHas('lead', function ($q) use ($filters) {
                $q->where('status', $filters['pipeline_stage']);
            });
        }
    }

    protected function getAnalyticsPage(): \App\Filament\Pages\AnalyticsDashboard
    {
        return \App\Filament\Pages\AnalyticsDashboard::getCurrentInstance() ?? app(\App\Filament\Pages\AnalyticsDashboard::class);
    }

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}
