<?php

namespace App\Filament\Widgets;

use App\Enums\LeadStatus;
use App\Models\Lead;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class LeadsByStatusWidget extends ChartWidget
{
    protected static ?string $heading = 'Leads by Status';
    
    protected static ?string $pollingInterval = '30s';
    
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        // Only show this widget to admins
        if (!Auth::user()?->isAdmin()) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $labels = [];
        $data = [];
        $colors = [];
        
        // Get lead counts for each status
        foreach (LeadStatus::cases() as $status) {
            $count = Lead::where('status', $status->value)->count();
            
            if ($count > 0) { // Only include statuses with leads
                $labels[] = $status->label();
                $data[] = $count;
                $colors[] = $this->getColorValue($status->color());
            }
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
        return Auth::user()?->isAdmin() ?? false;
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
