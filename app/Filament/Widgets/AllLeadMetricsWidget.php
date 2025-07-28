<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Lead;
use App\Models\Customer;
use App\Models\LeadCost;
use App\Enums\LeadStatus;
use Carbon\Carbon;

class AllLeadMetricsWidget extends BaseWidget
{
    public ?string $filter = null;

    protected function getStats(): array
    {
        // Get current year data for all leads
        $currentYearQuery = Lead::whereYear('created_at', Carbon::now()->year);
        $lastYearQuery = Lead::whereYear('created_at', Carbon::now()->subYear()->year);

        // Apply status filter if set
        if ($this->filter && $this->filter !== 'all') {
            $currentYearQuery->where('status', $this->filter);
            $lastYearQuery->where('status', $this->filter);
        }

        $currentYearLeads = $currentYearQuery->count();
        $lastYearLeads = $lastYearQuery->count();
        
        // Calculate leads percentage change
        $leadsChange = $lastYearLeads > 0 
            ? (($currentYearLeads - $lastYearLeads) / $lastYearLeads) * 100 
            : ($currentYearLeads > 0 ? 100 : 0);

        // Get conversion rate (confirmed leads vs total leads)
        $confirmedLeads = Lead::whereYear('created_at', Carbon::now()->year)
            ->where('status', LeadStatus::CONFIRMED->value)
            ->when($this->filter && $this->filter !== 'all', fn($q) => $q->where('status', $this->filter))
            ->count();
            
        $conversionRate = $currentYearLeads > 0 ? ($confirmedLeads / $currentYearLeads) * 100 : 0;

        // Get total revenue from confirmed leads
        $totalRevenue = LeadCost::whereHas('lead', function($query) {
            $query->whereYear('created_at', Carbon::now()->year);
            if ($this->filter && $this->filter !== 'all') {
                $query->where('status', $this->filter);
            }
        })->sum('amount');

        $lastYearRevenue = LeadCost::whereHas('lead', function($query) {
            $query->whereYear('created_at', Carbon::now()->subYear()->year);
            if ($this->filter && $this->filter !== 'all') {
                $query->where('status', $this->filter);
            }
        })->sum('amount');

        // Calculate revenue percentage change
        $revenueChange = $lastYearRevenue > 0 
            ? (($totalRevenue - $lastYearRevenue) / $lastYearRevenue) * 100 
            : ($totalRevenue > 0 ? 100 : 0);

        return [
            Stat::make('Total Leads', number_format($currentYearLeads))
                ->description(sprintf('%+.1f%% from last year', $leadsChange))
                ->descriptionIcon($leadsChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($leadsChange >= 0 ? 'success' : 'danger'),
                
            Stat::make('Conversion Rate', number_format($conversionRate, 1) . '%')
                ->description(sprintf('%d confirmed out of %d leads', $confirmedLeads, $currentYearLeads))
                ->descriptionIcon($conversionRate >= 15 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($conversionRate >= 15 ? 'success' : ($conversionRate >= 10 ? 'warning' : 'danger')),
                
            Stat::make('Total Revenue', '$' . number_format($totalRevenue, 2))
                ->description(sprintf('%+.1f%% from last year', $revenueChange))
                ->descriptionIcon($revenueChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueChange >= 0 ? 'success' : 'danger'),
        ];
    }

    protected function getFilters(): ?array
    {
        return [
            'all' => 'All Leads',
            LeadStatus::NEW->value => 'New',
            LeadStatus::ASSIGNED_TO_SALES->value => 'In Sales',
            LeadStatus::CONFIRMED->value => 'Confirmed',
            LeadStatus::MARK_CLOSED->value => 'Closed',
        ];
    }
} 