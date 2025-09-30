<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\User;
use App\Models\CallCenterCall;
use App\Services\DateRangeService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OperationsWorkloadWidget extends ChartWidget
{
    protected static ?string $heading = 'Operations Workload';
    protected static ?int $sort = 5;
    protected static ?string $pollingInterval = '30s';
    protected static bool $isLazy = true;

    protected function getData(): array
    {
        $analyticsPage = $this->getAnalyticsPage();
        $dateRange = $analyticsPage->getDateRange();
        $filters = $analyticsPage->getFilters();
        
        $cacheKey = 'operations_workload_' . $analyticsPage->getCacheKey();
        
        return Cache::remember($cacheKey, 300, function () use ($dateRange, $filters) {
            $startDate = $dateRange->getStartDate();
            $endDate = $dateRange->getEndDate();

            $query = CallCenterCall::query()
                ->join('users', 'call_center_calls.assigned_call_center_user', '=', 'users.id')
                ->whereBetween('call_center_calls.created_at', [$startDate, $endDate])
                ->whereIn('call_center_calls.status', ['pending', 'assigned'])
                ->where('users.role', 'operation');

            $this->applyFilters($query, $filters);

            $results = $query
                ->select(
                    'users.name as operation_user',
                    DB::raw('COUNT(call_center_calls.id) as total_tasks'),
                    DB::raw('SUM(CASE WHEN call_center_calls.created_at < DATE_SUB(NOW(), INTERVAL 2 DAY) THEN 1 ELSE 0 END) as overdue_tasks')
                )
                ->groupBy('users.id', 'users.name')
                ->orderByDesc('total_tasks')
                ->get();

            $labels = $results->pluck('operation_user')->toArray();
            $totalTasks = $results->pluck('total_tasks')->toArray();
            $overdueTasks = $results->pluck('overdue_tasks')->toArray();

            return [
                'datasets' => [
                    [
                        'label' => 'Total Tasks',
                        'data' => $totalTasks,
                        'backgroundColor' => 'rgba(59, 130, 246, 0.8)',
                        'borderColor' => 'rgb(59, 130, 246)',
                        'borderWidth' => 1,
                    ],
                    [
                        'label' => 'Overdue Tasks',
                        'data' => $overdueTasks,
                        'backgroundColor' => 'rgba(239, 68, 68, 0.8)',
                        'borderColor' => 'rgb(239, 68, 68)',
                        'borderWidth' => 1,
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
                            return context.dataset.label + ": " + context.parsed.y + " tasks";
                        }',
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'display' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Operation Staff',
                    ],
                ],
                'y' => [
                    'display' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Number of Tasks',
                    ],
                    'beginAtZero' => true,
                ],
            ],
        ];
    }

    protected function applyFilters($query, array $filters): void
    {
        if (isset($filters['operation_user'])) {
            $query->where('call_center_calls.assigned_call_center_user', $filters['operation_user']);
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
