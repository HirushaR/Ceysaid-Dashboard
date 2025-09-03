<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use App\Models\Invoice;
use App\Enums\LeadStatus;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesActivityWidget extends ChartWidget
{
    protected static ?string $heading = 'My Lead Activity (Last 30 Days)';
    
    protected static ?string $pollingInterval = '30s';
    
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $user = Auth::user();
        
        // Only show this widget to sales users and admins
        if (!$user || (!$user->isSales() && !$user->isAdmin())) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $userId = $user->id;
        $labels = [];
        $newLeadsData = [];
        $convertedLeadsData = [];
        
        // Get data for the last 30 days
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('M j');
            
            // Count new leads assigned on this day
            $newLeads = Lead::where('assigned_to', $userId)
                ->whereDate('created_at', $date->toDateString())
                ->count();
            $newLeadsData[] = $newLeads;
            
            // Count leads converted on this day
            $convertedLeads = Lead::where('assigned_to', $userId)
                ->whereIn('status', [
                    LeadStatus::CONFIRMED->value,
                    LeadStatus::OPERATION_COMPLETE->value,
                    LeadStatus::DOCUMENT_UPLOAD_COMPLETE->value
                ])
                ->whereDate('updated_at', $date->toDateString())
                ->count();
            $convertedLeadsData[] = $convertedLeads;
        }

        return [
            'datasets' => [
                [
                    'label' => 'New Leads Assigned',
                    'data' => $newLeadsData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => '#3B82F6',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Leads Converted',
                    'data' => $convertedLeadsData,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'borderColor' => '#10B981',
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
        return $user && ($user->isSales());
    }
}
