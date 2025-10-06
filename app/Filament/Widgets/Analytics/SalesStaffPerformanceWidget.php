<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\User;
use App\Models\Lead;
use App\Models\Invoice;
use App\Services\DateRangeService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SalesStaffPerformanceWidget extends StatsOverviewWidget
{
    protected ?string $heading = 'Sales Staff Performance';
    protected ?string $description = 'Top performers showing revenue, leads, and conversion metrics. Converted Leads = confirmed/operation complete/document upload complete. Conversion Rate = (Converted Leads ÷ Total Leads) × 100';
    protected static ?int $sort = 2;
    protected static ?string $pollingInterval = '30s';
    protected static bool $isLazy = true;

    protected function getStats(): array
    {
        $performanceData = $this->getPerformanceData();
        
        // Ensure we have an array (not a Collection)
        if ($performanceData instanceof \Illuminate\Support\Collection) {
            $performanceData = $performanceData->toArray();
        }
        
        if (empty($performanceData)) {
            return [
                Stat::make('No Sales Staff', 'No data available')
                    ->description('No sales staff found for the selected period')
                    ->color('gray'),
            ];
        }

        // Sort by total revenue descending
        usort($performanceData, fn($a, $b) => $b['total_revenue'] <=> $a['total_revenue']);
        
        $stats = [];
        
        // Show top 4 performers
        $topPerformers = array_slice($performanceData, 0, 4);
        
        foreach ($topPerformers as $index => $staff) {
            $stats[] = Stat::make($staff['name'], 'LKR ' . number_format($staff['total_revenue'], 0, ',', '.'))
                ->description(
                    $staff['total_leads'] . ' leads • ' . 
                    $staff['converted_leads'] . ' converted (' . $staff['conversion_rate'] . '%) • ' .
                    'Avg: LKR ' . number_format($staff['avg_deal_size'], 0, ',', '.') . 
                    "\n" . 'Converted Leads: Leads that reached confirmed, operation complete, or document upload complete status' .
                    "\n" . 'Conversion Rate: Percentage of total leads successfully converted (≥20% = Excellent, 10-19% = Good, <10% = Needs Improvement)'
                )
                ->color($this->getPerformanceColor($staff['conversion_rate']))
                ->chart($this->getMiniChart($staff));
        }
        
        return $stats;
    }

    protected function getPerformanceData()
    {
        $analyticsPage = $this->getAnalyticsPage();
        $dateRange = $analyticsPage->getDateRange();
        $filters = $analyticsPage->getFilters();
        
        $cacheKey = 'sales_staff_performance_' . $analyticsPage->getCacheKey();
        
        return Cache::remember($cacheKey, 300, function () use ($dateRange, $filters) {
            $startDate = $dateRange->getStartDate();
            $endDate = $dateRange->getEndDate();
            $now = now();

            // Get all sales users
            $salesUsers = User::where('role', 'sales')->get();

            $performanceData = [];

            foreach ($salesUsers as $user) {
                $userId = $user->id;

                // Apply filters
                if (isset($filters['sales_user']) && $filters['sales_user'] != $userId) {
                    continue;
                }

                // Get leads assigned to this sales user
                $leadsQuery = Lead::where('assigned_to', $userId);
                
                // Apply date range filter
                $leadsQuery->whereBetween('created_at', [$startDate, $endDate]);
                
                // Apply other filters
                if (isset($filters['lead_source'])) {
                    $leadsQuery->where('platform', $filters['lead_source']);
                }
                
                if (isset($filters['pipeline_stage'])) {
                    $leadsQuery->where('status', $filters['pipeline_stage']);
                }

                $totalLeads = $leadsQuery->count();
                
                // Get converted leads
                $convertedLeads = (clone $leadsQuery)->whereIn('status', [
                    'confirmed', 
                    'operation_complete', 
                    'document_upload_complete'
                ])->count();
                
                $conversionRate = $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 1) : 0;
                
                // Get revenue from invoices
                $revenueQuery = Invoice::whereHas('lead', function($query) use ($userId, $startDate, $endDate) {
                    $query->where('assigned_to', $userId)
                          ->whereBetween('created_at', [$startDate, $endDate]);
                });
                
                $totalRevenue = $revenueQuery->sum('total_amount');
                $avgDealSize = $convertedLeads > 0 ? $totalRevenue / $convertedLeads : 0;
                
                // Get active leads (not closed or completed)
                $activeLeads = (clone $leadsQuery)->whereNotIn('status', [
                    'mark_closed',
                    'operation_complete', 
                    'document_upload_complete'
                ])->count();
                
                // Get pending leads (new, assigned, pricing)
                $pendingLeads = (clone $leadsQuery)->whereIn('status', [
                    'new',
                    'assigned_to_sales',
                    'pricing_in_progress'
                ])->count();
                
                // Get this month's revenue
                $thisMonthRevenue = Invoice::whereHas('lead', function($query) use ($userId) {
                    $query->where('assigned_to', $userId);
                })->whereYear('created_at', $now->year)
                  ->whereMonth('created_at', $now->month)
                  ->sum('total_amount');

                $performanceData[] = [
                    'id' => $userId,
                    'name' => $user->name,
                    'total_leads' => $totalLeads,
                    'converted_leads' => $convertedLeads,
                    'conversion_rate' => $conversionRate,
                    'total_revenue' => $totalRevenue,
                    'avg_deal_size' => $avgDealSize,
                    'active_leads' => $activeLeads,
                    'pending_leads' => $pendingLeads,
                    'this_month_revenue' => $thisMonthRevenue,
                ];
            }

            // Return array for stats widget (ensure it's not a Collection)
            return is_array($performanceData) ? $performanceData : $performanceData->toArray();
        });
    }

    protected function getPerformanceColor(float $conversionRate): string
    {
        if ($conversionRate >= 20) {
            return 'success';
        } elseif ($conversionRate >= 10) {
            return 'warning';
        } else {
            return 'danger';
        }
    }

    protected function getMiniChart(array $staff): array
    {
        // Simple chart data showing monthly progression
        return [
            $staff['this_month_revenue'] / 1000, // Convert to thousands for chart
            $staff['total_revenue'] / 1000,
            $staff['avg_deal_size'] / 1000,
        ];
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
