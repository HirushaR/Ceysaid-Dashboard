<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use App\Enums\ServiceStatus;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class OperationServiceStatusWidget extends ChartWidget
{
    protected static ?string $heading = 'Service Status Overview';
    
    protected static ?string $pollingInterval = '30s';
    
    protected static ?int $sort = 4;

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
        
        // Get service status counts for leads assigned to this operation user
        $serviceData = [];
        $serviceLabels = ['Air Tickets', 'Hotel', 'Visa', 'Land Package'];
        $serviceFields = ['air_ticket_status', 'hotel_status', 'visa_status', 'land_package_status'];
        
        $pendingData = [];
        $doneData = [];
        $notRequiredData = [];
        
        foreach ($serviceFields as $field) {
            $pending = Lead::where('assigned_operator', $userId)
                ->where($field, ServiceStatus::PENDING->value)
                ->count();
            $done = Lead::where('assigned_operator', $userId)
                ->where($field, ServiceStatus::DONE->value)
                ->count();
            $notRequired = Lead::where('assigned_operator', $userId)
                ->where($field, ServiceStatus::NOT_REQUIRED->value)
                ->count();
                
            $pendingData[] = $pending;
            $doneData[] = $done;
            $notRequiredData[] = $notRequired;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pending',
                    'data' => $pendingData,
                    'backgroundColor' => 'rgba(245, 158, 11, 0.8)',
                    'borderColor' => '#F59E0B',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Done',
                    'data' => $doneData,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.8)',
                    'borderColor' => '#10B981',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Not Required',
                    'data' => $notRequiredData,
                    'backgroundColor' => 'rgba(107, 114, 128, 0.8)',
                    'borderColor' => '#6B7280',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $serviceLabels,
        ];
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
            'aspectRatio' => 2,
            'scales' => [
                'x' => [
                    'stacked' => true,
                ],
                'y' => [
                    'beginAtZero' => true,
                    'stacked' => true,
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
        return $user && ($user->isOperation());
    }
}
