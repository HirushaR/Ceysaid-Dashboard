<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\Lead;
use App\Enums\LeadStatus;
use App\Services\DateRangeService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class PipelineBreakdownWidget extends ChartWidget
{
    protected static ?string $heading = 'Pipeline Stage Breakdown';
    protected static ?int $sort = 4;
    protected static ?string $pollingInterval = '30s';
    protected static bool $isLazy = true;

    protected function getData(): array
    {
        $analyticsPage = $this->getAnalyticsPage();
        $dateRange = $analyticsPage->getDateRange();
        $filters = $analyticsPage->getFilters();
        
        $cacheKey = 'pipeline_breakdown_' . $analyticsPage->getCacheKey();
        
        return Cache::remember($cacheKey, 300, function () use ($dateRange, $filters) {
            $startDate = $dateRange->getStartDate();
            $endDate = $dateRange->getEndDate();

            $query = Lead::query()
                ->whereBetween('created_at', [$startDate, $endDate]);

            $this->applyFilters($query, $filters);

            $results = $query
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->orderByDesc('count')
                ->get();

            $labels = [];
            $data = [];
            $colors = [];

            foreach ($results as $result) {
                $status = LeadStatus::tryFrom($result->status);
                $labels[] = $status ? $status->label() : ucfirst($result->status);
                $data[] = $result->count;
                $colors[] = $this->getStatusColor($result->status);
            }

            return [
                'datasets' => [
                    [
                        'label' => 'Leads by Status',
                        'data' => $data,
                        'backgroundColor' => $colors,
                        'borderWidth' => 1,
                    ],
                ],
                'labels' => $labels,
            ];
        });
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'right',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return context.label + ": " + context.parsed + " (" + percentage + "%)";
                        }',
                    ],
                ],
            ],
        ];
    }

    protected function getStatusColor(string $status): string
    {
        $statusEnum = LeadStatus::tryFrom($status);
        if (!$statusEnum) {
            return 'rgba(156, 163, 175, 0.8)'; // gray
        }

        return match ($statusEnum->color()) {
            'gray' => 'rgba(156, 163, 175, 0.8)',
            'info' => 'rgba(59, 130, 246, 0.8)',
            'warning' => 'rgba(245, 158, 11, 0.8)',
            'success' => 'rgba(34, 197, 94, 0.8)',
            'danger' => 'rgba(239, 68, 68, 0.8)',
            'primary' => 'rgba(99, 102, 241, 0.8)',
            'secondary' => 'rgba(107, 114, 128, 0.8)',
            'company' => 'rgba(139, 69, 19, 0.8)',
            'accent' => 'rgba(168, 85, 247, 0.8)',
            'brand' => 'rgba(236, 72, 153, 0.8)',
            default => 'rgba(156, 163, 175, 0.8)',
        };
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
