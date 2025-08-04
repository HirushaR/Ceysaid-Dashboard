<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Lead;
use App\Enums\LeadStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeadsByStatusWidget extends ChartWidget
{
    public function getHeading(): string
    {
        return 'Leads by Status';
    }

    protected static ?string $maxHeight = '300px';
    protected static ?string $pollingInterval = null;

    protected function getData(): array
    {
        $user = auth()->user();
        
        // Get leads by status for current year
        $leadsByStatus = Lead::where('assigned_to', $user ? $user->id : null)
            ->whereYear('created_at', Carbon::now()->year)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Define Chart.js compatible colors for each status
        $statusColors = [
            LeadStatus::NEW->value => '#6b7280', // gray
            LeadStatus::ASSIGNED_TO_SALES->value => '#3b82f6', // blue
            LeadStatus::ASSIGNED_TO_OPERATIONS->value => '#f59e0b', // amber
            LeadStatus::INFO_GATHER_COMPLETE->value => '#10b981', // emerald
            LeadStatus::MARK_COMPLETED->value => '#8b5cf6', // violet
            LeadStatus::MARK_CLOSED->value => '#ef4444', // red
            LeadStatus::PRICING_IN_PROGRESS->value => '#6366f1', // indigo
            LeadStatus::SENT_TO_CUSTOMER->value => '#f97316', // orange
            LeadStatus::OPERATION_COMPLETE->value => '#06b6d4', // cyan
            LeadStatus::CONFIRMED->value => '#84cc16', // lime
            LeadStatus::DOCUMENT_UPLOAD_COMPLETE->value => '#ec4899', // pink
        ];

        // Get all possible statuses and their labels
        $statusLabels = [];
        $colors = [];
        $statusData = [];
        
        foreach (LeadStatus::cases() as $status) {
            $statusLabels[] = $status->label();
            $colors[] = $statusColors[$status->value] ?? '#6b7280'; // default gray
            $statusData[] = $leadsByStatus[$status->value] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Leads',
                    'data' => $statusData,
                    'backgroundColor' => $colors,
                    'borderColor' => $colors,
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $statusLabels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            return context.label + ": " + context.parsed + " leads";
                        }',
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
} 