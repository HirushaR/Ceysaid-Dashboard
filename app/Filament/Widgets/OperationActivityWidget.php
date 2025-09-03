<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use App\Enums\LeadStatus;
use App\Enums\ServiceStatus;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class OperationActivityWidget extends ChartWidget
{
    protected static ?string $heading = 'My Operation Activity (Last 30 Days)';
    
    protected static ?string $pollingInterval = '30s';
    
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $user = Auth::user();
        
        // Only show this widget to operation users and admins
        if (!$user || (!$user->isOperation() && !$user->isAdmin())) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $userId = $user->id;
        $labels = [];
        $assignedLeadsData = [];
        $completedLeadsData = [];
        $serviceCompletedData = [];
        
        // Get data for the last 30 days
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('M j');
            
            // Count leads assigned to operations on this day
            $assignedLeads = Lead::where('assigned_operator', $userId)
                ->whereDate('created_at', $date->toDateString())
                ->count();
            $assignedLeadsData[] = $assignedLeads;
            
            // Count leads completed on this day
            $completedLeads = Lead::where('assigned_operator', $userId)
                ->where('status', LeadStatus::OPERATION_COMPLETE->value)
                ->whereDate('updated_at', $date->toDateString())
                ->count();
            $completedLeadsData[] = $completedLeads;
            
            // Count service completions on this day (approximate based on status updates)
            $serviceCompleted = Lead::where('assigned_operator', $userId)
                ->where(function($query) {
                    $query->where('air_ticket_status', ServiceStatus::DONE->value)
                          ->orWhere('hotel_status', ServiceStatus::DONE->value)
                          ->orWhere('visa_status', ServiceStatus::DONE->value)
                          ->orWhere('land_package_status', ServiceStatus::DONE->value);
                })
                ->whereDate('updated_at', $date->toDateString())
                ->count();
            $serviceCompletedData[] = $serviceCompleted;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Leads Assigned',
                    'data' => $assignedLeadsData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => '#3B82F6',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Leads Completed',
                    'data' => $completedLeadsData,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'borderColor' => '#10B981',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Services Completed',
                    'data' => $serviceCompletedData,
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'borderColor' => '#F59E0B',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
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
            'aspectRatio' => 2,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                    'labels' => [
                        'color' => '#374151',
                        'font' => [
                            'size' => 12,
                            'weight' => '500',
                        ],
                        'usePointStyle' => true,
                        'pointStyle' => 'circle',
                    ],
                ],
                'tooltip' => [
                    'backgroundColor' => 'rgba(0, 0, 0, 0.8)',
                    'titleColor' => '#FFFFFF',
                    'bodyColor' => '#FFFFFF',
                    'borderColor' => '#E5E7EB',
                    'borderWidth' => 1,
                    'cornerRadius' => 8,
                ]
            ],
        ];
    }

    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && ($user->isOperation() || $user->isAdmin());
    }
}
