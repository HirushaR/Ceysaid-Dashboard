<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Lead;
use App\Models\Customer;
use App\Models\LeadCost;
use App\Enums\LeadStatus;
use Carbon\Carbon;

class SalesMetricsWidget extends BaseWidget
{
    public ?string $filter = null;

    protected function getStats(): array
    {
        $user = auth()->user();
        
        // Get current year data for the sales person
        $currentYearQuery = Lead::where('assigned_to', $user ? $user->id : null)
            ->whereYear('created_at', Carbon::now()->year);
        
        // Get last year data for comparison
        $lastYearQuery = Lead::where('assigned_to', $user ? $user->id : null)
            ->whereYear('created_at', Carbon::now()->subYear()->year);

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

        // Get unique customers count
        $currentYearCustomers = Lead::where('assigned_to', $user ? $user->id : null)
            ->whereYear('created_at', Carbon::now()->year)
            ->when($this->filter && $this->filter !== 'all', fn($q) => $q->where('status', $this->filter))
            ->distinct('customer_id')
            ->count('customer_id');
            
        $lastYearCustomers = Lead::where('assigned_to', $user ? $user->id : null)
            ->whereYear('created_at', Carbon::now()->subYear()->year)
            ->when($this->filter && $this->filter !== 'all', fn($q) => $q->where('status', $this->filter))
            ->distinct('customer_id')
            ->count('customer_id');

        // Calculate customers percentage change
        $customersChange = $lastYearCustomers > 0 
            ? (($currentYearCustomers - $lastYearCustomers) / $lastYearCustomers) * 100 
            : ($currentYearCustomers > 0 ? 100 : 0);

        // Get revenue from lead costs
        $currentYearRevenue = LeadCost::whereHas('lead', function($query) use ($user) {
            $query->where('assigned_to', $user ? $user->id : null)
                ->whereYear('created_at', Carbon::now()->year);
            if ($this->filter && $this->filter !== 'all') {
                $query->where('status', $this->filter);
            }
        })->sum('amount');

        $lastYearRevenue = LeadCost::whereHas('lead', function($query) use ($user) {
            $query->where('assigned_to', $user ? $user->id : null)
                ->whereYear('created_at', Carbon::now()->subYear()->year);
            if ($this->filter && $this->filter !== 'all') {
                $query->where('status', $this->filter);
            }
        })->sum('amount');

        // Calculate revenue percentage change
        $revenueChange = $lastYearRevenue > 0 
            ? (($currentYearRevenue - $lastYearRevenue) / $lastYearRevenue) * 100 
            : ($currentYearRevenue > 0 ? 100 : 0);

        return [
            Stat::make('Leads', number_format($currentYearLeads))
                ->description(sprintf('%+.1f%% from last year', $leadsChange))
                ->descriptionIcon($leadsChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($leadsChange >= 0 ? 'success' : 'danger'),
                
            Stat::make('Customers', number_format($currentYearCustomers))
                ->description(sprintf('%+.1f%% from last year', $customersChange))
                ->descriptionIcon($customersChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($customersChange >= 0 ? 'success' : 'danger'),
                
            Stat::make('Revenue', '$' . number_format($currentYearRevenue, 2))
                ->description(sprintf('%+.1f%% from last year', $revenueChange))
                ->descriptionIcon($revenueChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueChange >= 0 ? 'success' : 'danger'),
        ];
    }

    protected function getFilters(): ?array
    {
        return [
            'all' => 'All',
            LeadStatus::ASSIGNED_TO_SALES->value => 'Open',
            LeadStatus::CONFIRMED->value => 'Confirmed',
            LeadStatus::MARK_CLOSED->value => 'Closed',
        ];
    }
} 