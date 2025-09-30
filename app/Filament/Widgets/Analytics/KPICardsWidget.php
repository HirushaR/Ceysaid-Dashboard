<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\Lead;
use App\Models\Invoice;
use App\Models\CallCenterCall;
use App\Services\DateRangeService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class KPICardsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    protected static bool $isLazy = true;

    protected function getStats(): array
    {
        $analyticsPage = $this->getAnalyticsPage();
        $dateRange = $analyticsPage->getDateRange();
        $filters = $analyticsPage->getFilters();
        
        $cacheKey = 'kpi_cards_' . $analyticsPage->getCacheKey();
        
        return Cache::remember($cacheKey, 300, function () use ($dateRange, $filters) {
            return [
                $this->getTotalLeadsStat($dateRange, $filters),
                $this->getConversionRateStat($dateRange, $filters),
                $this->getTotalRevenueStat($dateRange, $filters),
                $this->getPendingTasksStat($dateRange, $filters),
            ];
        });
    }

    protected function getTotalLeadsStat(DateRangeService $dateRange, array $filters): Stat
    {
        $query = Lead::query()
            ->whereBetween('created_at', [$dateRange->getStartDate(), $dateRange->getEndDate()]);

        $this->applyFilters($query, $filters);

        $totalLeads = $query->count();
        
        // Get previous period for comparison
        $previousStart = $dateRange->getStartDate()->copy()->sub($dateRange->getDaysDiff() + 1, 'days');
        $previousEnd = $dateRange->getStartDate()->copy()->subDay();
        
        $previousQuery = Lead::query()
            ->whereBetween('created_at', [$previousStart, $previousEnd]);
        $this->applyFilters($previousQuery, $filters);
        $previousLeads = $previousQuery->count();

        $change = $previousLeads > 0 ? (($totalLeads - $previousLeads) / $previousLeads) * 100 : 0;

        return Stat::make('Total Leads', number_format($totalLeads))
            ->description($this->getChangeDescription($change))
            ->descriptionIcon($change >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
            ->color($change >= 0 ? 'success' : 'danger')
            ->url(\App\Filament\Resources\LeadResource::getUrl('index', [
                'tableFilters' => [
                    'created_at' => [
                        'created_from' => $dateRange->getStartDate()->toDateString(),
                        'created_until' => $dateRange->getEndDate()->toDateString(),
                    ],
                ],
            ]));
    }

    protected function getConversionRateStat(DateRangeService $dateRange, array $filters): Stat
    {
        $leadsQuery = Lead::query()
            ->whereBetween('created_at', [$dateRange->getStartDate(), $dateRange->getEndDate()]);
        $this->applyFilters($leadsQuery, $filters);
        $totalLeads = $leadsQuery->count();

        $convertedQuery = Lead::query()
            ->whereBetween('created_at', [$dateRange->getStartDate(), $dateRange->getEndDate()])
            ->whereIn('status', ['confirmed', 'document_upload_complete']);
        $this->applyFilters($convertedQuery, $filters);
        $convertedLeads = $convertedQuery->count();

        $conversionRate = $totalLeads > 0 ? ($convertedLeads / $totalLeads) * 100 : 0;

        return Stat::make('Conversion Rate', number_format($conversionRate, 1) . '%')
            ->description($convertedLeads . ' of ' . $totalLeads . ' leads converted')
            ->color($conversionRate >= 20 ? 'success' : ($conversionRate >= 10 ? 'warning' : 'danger'));
    }

    protected function getTotalRevenueStat(DateRangeService $dateRange, array $filters): Stat
    {
        $query = Invoice::query()
            ->whereBetween('created_at', [$dateRange->getStartDate(), $dateRange->getEndDate()])
            ->where('customer_payment_status', 'paid');

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

        $totalRevenue = $query->sum('total_amount');

        // Get previous period for comparison
        $previousStart = $dateRange->getStartDate()->copy()->sub($dateRange->getDaysDiff() + 1, 'days');
        $previousEnd = $dateRange->getStartDate()->copy()->subDay();
        
        $previousQuery = Invoice::query()
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->where('customer_payment_status', 'paid');
        
        if (isset($filters['sales_user'])) {
            $previousQuery->whereHas('lead', function ($q) use ($filters) {
                $q->where('assigned_to', $filters['sales_user']);
            });
        }

        if (isset($filters['lead_source'])) {
            $previousQuery->whereHas('lead', function ($q) use ($filters) {
                $q->where('platform', $filters['lead_source']);
            });
        }

        if (isset($filters['pipeline_stage'])) {
            $previousQuery->whereHas('lead', function ($q) use ($filters) {
                $q->where('status', $filters['pipeline_stage']);
            });
        }
        
        $previousRevenue = $previousQuery->sum('total_amount');
        $change = $previousRevenue > 0 ? (($totalRevenue - $previousRevenue) / $previousRevenue) * 100 : 0;

        return Stat::make('Total Revenue', 'LKR ' . number_format($totalRevenue, 2))
            ->description($this->getChangeDescription($change))
            ->descriptionIcon($change >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
            ->color($change >= 0 ? 'success' : 'danger')
            ->url(\App\Filament\Resources\InvoiceResource::getUrl('index', [
                'tableFilters' => [
                    'created_at' => [
                        'created_from' => $dateRange->getStartDate()->toDateString(),
                        'created_until' => $dateRange->getEndDate()->toDateString(),
                    ],
                    'customer_payment_status' => 'paid',
                ],
            ]));
    }

    protected function getPendingTasksStat(DateRangeService $dateRange, array $filters): Stat
    {
        $query = CallCenterCall::query()
            ->whereBetween('created_at', [$dateRange->getStartDate(), $dateRange->getEndDate()])
            ->whereIn('status', ['pending', 'assigned']);

        if (isset($filters['operation_user'])) {
            $query->where('assigned_call_center_user', $filters['operation_user']);
        }

        $pendingTasks = $query->count();

        // Count overdue tasks
        $overdueQuery = CallCenterCall::query()
            ->whereBetween('created_at', [$dateRange->getStartDate(), $dateRange->getEndDate()])
            ->whereIn('status', ['pending', 'assigned'])
            ->where('created_at', '<', now()->subDays(2));

        if (isset($filters['operation_user'])) {
            $overdueQuery->where('assigned_call_center_user', $filters['operation_user']);
        }

        $overdueTasks = $overdueQuery->count();

        return Stat::make('Pending Tasks', number_format($pendingTasks))
            ->description($overdueTasks . ' overdue')
            ->color($overdueTasks > 0 ? 'danger' : ($pendingTasks > 10 ? 'warning' : 'success'))
            ->url(\App\Filament\Resources\CallCenterCallResource::getUrl('index', [
                'tableFilters' => [
                    'status' => ['pending', 'assigned'],
                ],
            ]));
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

    protected function getChangeDescription(float $change): string
    {
        $absChange = abs($change);
        $direction = $change >= 0 ? 'up' : 'down';
        
        if ($absChange < 1) {
            return 'No change from previous period';
        }
        
        return number_format($absChange, 1) . '% ' . $direction . ' from previous period';
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
