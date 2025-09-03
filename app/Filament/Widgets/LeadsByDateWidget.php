<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LeadsByDateWidget extends ChartWidget
{
    protected static ?string $heading = 'Leads by Date';
    
    protected static ?string $pollingInterval = '30s';
    
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        // Only show this widget to admins
        if (!Auth::user()?->isAdmin()) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        // Get leads created in the last 30 days
        $leads = Lead::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        $labels = [];
        $data = [];
        
        // Fill in missing dates with 0 counts
        $startDate = now()->subDays(30);
        $endDate = now();
        
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dateString = $date->format('Y-m-d');
            $labels[] = $date->format('M j');
            
            $leadCount = $leads->where('date', $dateString)->first();
            $data[] = $leadCount ? $leadCount->count : 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Leads Created',
                    'data' => $data,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => '#3B82F6',
                    'borderWidth' => 3,
                    'fill' => true,
                    'tension' => 0.4,
                    'pointBackgroundColor' => '#3B82F6',
                    'pointBorderColor' => '#FFFFFF',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 5,
                    'pointHoverRadius' => 7,
                    'pointHoverBackgroundColor' => '#2563EB',
                    'pointHoverBorderColor' => '#FFFFFF',
                    'pointHoverBorderWidth' => 3,
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
            'aspectRatio' => 1,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                        'color' => '#6B7280',
                        'font' => [
                            'size' => 12,
                        ],
                    ],
                    'grid' => [
                        'color' => '#F3F4F6',
                    ],
                ],
                'x' => [
                    'ticks' => [
                        'maxTicksLimit' => 8,
                        'color' => '#6B7280',
                        'font' => [
                            'size' => 12,
                        ],
                    ],
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                    'labels' => [
                        'color' => '#374151',
                        'font' => [
                            'size' => 13,
                            'weight' => '500',
                        ],
                        'padding' => 20,
                    ],
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'backgroundColor' => 'rgba(0, 0, 0, 0.8)',
                    'titleColor' => '#FFFFFF',
                    'bodyColor' => '#FFFFFF',
                    'borderColor' => '#E5E7EB',
                    'borderWidth' => 1,
                    'cornerRadius' => 8,
                    'callbacks' => [
                        'title' => 'function(context) {
                            const date = new Date(context[0].label + ", " + new Date().getFullYear());
                            return date.toLocaleDateString("en-US", { 
                                weekday: "long", 
                                year: "numeric", 
                                month: "long", 
                                day: "numeric" 
                            });
                        }',
                        'label' => 'function(context) {
                            return "Leads: " + context.parsed.y;
                        }'
                    ]
                ]
            ],
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
        ];
    }

    public static function canView(): bool
    {
        return Auth::user()?->isAdmin() ?? false;
    }
}
