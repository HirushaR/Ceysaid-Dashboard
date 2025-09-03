<?php

namespace App\Filament\Widgets;

use App\Enums\LeadStatus;
use App\Models\Lead;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class OperationLeadStatusWidget extends ChartWidget
{
    protected static ?string $heading = 'My Operation Leads by Status';
    
    protected static ?string $pollingInterval = '30s';
    
    protected static ?int $sort = 2;

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
        $data = [];
        $colors = [];
        
        // Get lead counts for each status for this operation user
        // Focus on operation-relevant statuses
        $operationStatuses = [
            LeadStatus::ASSIGNED_TO_OPERATIONS,
            LeadStatus::INFO_GATHER_COMPLETE,
            LeadStatus::OPERATION_COMPLETE,
            LeadStatus::DOCUMENT_UPLOAD_COMPLETE,
        ];
        
        foreach ($operationStatuses as $status) {
            $count = Lead::where('assigned_operator', $userId)
                ->where('status', $status->value)
                ->count();
            
            if ($count > 0) { // Only include statuses with leads
                $labels[] = $status->label();
                $data[] = $count;
                $colors[] = $this->getColorValue($status->color());
            }
        }

        // If no leads, show a message
        if (empty($data)) {
            return [
                'datasets' => [
                    [
                        'label' => 'No Leads Assigned',
                        'data' => [1],
                        'backgroundColor' => ['#E5E7EB'],
                        'borderColor' => ['#D1D5DB'],
                        'borderWidth' => 2,
                    ],
                ],
                'labels' => ['No operation leads assigned to you yet'],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Number of Leads',
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderColor' => $colors,
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
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
            'aspectRatio' => 1,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'color' => '#374151',
                        'font' => [
                            'size' => 12,
                            'weight' => '500',
                        ],
                        'padding' => 15,
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
                    'callbacks' => [
                        'label' => 'function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return context.label + ": " + context.parsed + " (" + percentage + "%)";
                        }'
                    ]
                ]
            ],
        ];
    }

    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && ($user->isOperation() || $user->isAdmin());
    }

    private function getColorValue(string $color): string
    {
        return match($color) {
            'gray' => '#6B7280',
            'info' => '#3B82F6',
            'warning' => '#F59E0B',
            'success' => '#10B981',
            'danger' => '#EF4444',
            'primary' => '#8B5CF6',
            'secondary' => '#64748B',
            'company' => '#7C3AED',
            'accent' => '#EC4899',
            'brand' => '#F97316',
            default => '#6B7280',
        };
    }
}
