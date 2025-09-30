<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\User;
use App\Models\Lead;
use App\Models\Invoice;
use App\Services\DateRangeService;
use Filament\Widgets\TableWidget;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SalesStaffPerformanceWidget extends TableWidget
{
    protected static ?string $heading = 'Sales Staff Performance';
    protected static ?int $sort = 2;
    protected static ?string $pollingInterval = '30s';
    protected static bool $isLazy = true;

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getQuery())
            ->columns([
                TextColumn::make('name')
                    ->label('Sales Staff')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                TextColumn::make('total_leads')
                    ->label('Total Leads')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
                
                TextColumn::make('converted_leads')
                    ->label('Converted')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->color('success'),
                
                TextColumn::make('conversion_rate')
                    ->label('Conversion Rate')
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->sortable()
                    ->alignCenter()
                    ->color(fn ($state) => $state >= 20 ? 'success' : ($state >= 10 ? 'warning' : 'danger')),
                
                TextColumn::make('total_revenue')
                    ->label('Total Revenue')
                    ->formatStateUsing(fn ($state) => 'LKR ' . number_format($state, 0, ',', '.'))
                    ->sortable()
                    ->alignCenter()
                    ->color('success'),
                
                TextColumn::make('avg_deal_size')
                    ->label('Avg Deal Size')
                    ->formatStateUsing(fn ($state) => 'LKR ' . number_format($state, 0, ',', '.'))
                    ->sortable()
                    ->alignCenter(),
                
                TextColumn::make('active_leads')
                    ->label('Active Leads')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->color('warning'),
                
                TextColumn::make('pending_leads')
                    ->label('Pending')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->color('danger'),
                
                TextColumn::make('this_month_revenue')
                    ->label('This Month')
                    ->formatStateUsing(fn ($state) => 'LKR ' . number_format($state, 0, ',', '.'))
                    ->sortable()
                    ->alignCenter()
                    ->color('info'),
            ])
            ->defaultSort('total_revenue', 'desc')
            ->paginated(false)
            ->striped();
    }

    protected function getQuery()
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

            // Convert to collection for table widget
            return collect($performanceData);
        });
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
