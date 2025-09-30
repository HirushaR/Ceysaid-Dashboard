<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\Lead;
use App\Services\DateRangeService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LeadsTrendWidget extends ChartWidget
{
    protected static ?string $heading = 'Leads Trend';
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '30s';
    protected static bool $isLazy = true;

    protected function getData(): array
    {
        $analyticsPage = $this->getAnalyticsPage();
        $dateRange = $analyticsPage->getDateRange();
        $filters = $analyticsPage->getFilters();
        
        $cacheKey = 'leads_trend_' . $analyticsPage->getCacheKey();
        
        return Cache::remember($cacheKey, 300, function () use ($dateRange, $filters) {
            $interval = $dateRange->getInterval();
            $startDate = $dateRange->getStartDate();
            $endDate = $dateRange->getEndDate();

            $query = Lead::query()
                ->whereBetween('created_at', [$startDate, $endDate]);

            $this->applyFilters($query, $filters);

            $data = $this->getGroupedData($query, $interval, $startDate, $endDate);

            return [
                'datasets' => [
                    [
                        'label' => 'Leads',
                        'data' => array_values($data),
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'borderColor' => 'rgb(59, 130, 246)',
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
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->pluck('count', 'period')
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
                        'text' => 'Number of Leads',
                    ],
                    'beginAtZero' => true,
                ],
            ],
        ];
    }

    protected function applyFilters($query, array $filters): void
    {
        if (isset($filters['sales_user'])) {
            $query->where('assigned_to', $filters['sales_user']);
        }

        if (isset($filters['lead_source'])) {
            $query->where('platform', $filters['lead_source']);
        }

        if (isset($filters['pipeline_stage'])) {
            $query->where('status', $filters['pipeline_stage']);
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
