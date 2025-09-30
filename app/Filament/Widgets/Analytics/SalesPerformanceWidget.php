<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\User;
use App\Models\Invoice;
use App\Services\DateRangeService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SalesPerformanceWidget extends ChartWidget
{
    protected static ?string $heading = 'Sales Performance by Staff';
    protected static ?int $sort = 3;
    protected static ?string $pollingInterval = '30s';
    protected static bool $isLazy = true;

    protected function getData(): array
    {
        $analyticsPage = $this->getAnalyticsPage();
        $dateRange = $analyticsPage->getDateRange();
        $filters = $analyticsPage->getFilters();
        
        $cacheKey = 'sales_performance_' . $analyticsPage->getCacheKey();
        
        return Cache::remember($cacheKey, 300, function () use ($dateRange, $filters) {
            $startDate = $dateRange->getStartDate();
            $endDate = $dateRange->getEndDate();

            $query = Invoice::query()
                ->join('leads', 'invoices.lead_id', '=', 'leads.id')
                ->join('users', 'leads.assigned_to', '=', 'users.id')
                ->whereBetween('invoices.created_at', [$startDate, $endDate])
                ->where('invoices.customer_payment_status', 'paid')
                ->where('users.role', 'sales');

            $this->applyFilters($query, $filters);

            $results = $query
                ->select(
                    'users.name as sales_user',
                    DB::raw('COUNT(invoices.id) as bookings_count'),
                    DB::raw('SUM(invoices.total_amount) as total_revenue')
                )
                ->groupBy('users.id', 'users.name')
                ->orderByDesc('total_revenue')
                ->get();

            $labels = $results->pluck('sales_user')->toArray();
            $bookingsData = $results->pluck('bookings_count')->toArray();
            $revenueData = $results->pluck('total_revenue')->toArray();

            return [
                'datasets' => [
                    [
                        'label' => 'Bookings Count',
                        'data' => $bookingsData,
                        'backgroundColor' => 'rgba(59, 130, 246, 0.8)',
                        'borderColor' => 'rgb(59, 130, 246)',
                        'borderWidth' => 1,
                        'yAxisID' => 'y',
                    ],
                    [
                        'label' => 'Revenue ($)',
                        'data' => $revenueData,
                        'backgroundColor' => 'rgba(34, 197, 94, 0.8)',
                        'borderColor' => 'rgb(34, 197, 94)',
                        'borderWidth' => 1,
                        'yAxisID' => 'y1',
                    ],
                ],
                'labels' => $labels,
            ];
        });
    }

    protected function getType(): string
    {
        return 'bar';
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
                            if (context.datasetIndex === 0) {
                                return context.dataset.label + ": " + context.parsed.y + " bookings";
                            } else {
                                return context.dataset.label + ": $" + context.parsed.y.toLocaleString();
                            }
                        }',
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'display' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Sales Staff',
                    ],
                ],
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Bookings Count',
                    ],
                    'beginAtZero' => true,
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
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
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
        ];
    }

    protected function applyFilters($query, array $filters): void
    {
        if (isset($filters['sales_user'])) {
            $query->where('leads.assigned_to', $filters['sales_user']);
        }

        if (isset($filters['lead_source'])) {
            $query->where('leads.platform', $filters['lead_source']);
        }

        if (isset($filters['pipeline_stage'])) {
            $query->where('leads.status', $filters['pipeline_stage']);
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
