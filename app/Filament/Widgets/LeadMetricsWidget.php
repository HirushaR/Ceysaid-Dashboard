<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class LeadMetricsWidget extends ChartWidget
{
    protected static ?string $heading = 'Leads by Status';

    protected function getData(): array
    {
        $statuses = [
            'info_gather_complete' => 'Info Gather Complete',
            'mark_completed' => 'Mark Completed',
            'mark_closed' => 'Mark Closed',
            'pricing_in_progress' => 'Pricing In Progress',
            'sent_to_customer' => 'Sent to Customer',
        ];
        $counts = [];
        foreach ($statuses as $key => $label) {
            $counts[] = \App\Models\Lead::where('status', $key)->count();
        }
        return [
            'datasets' => [
                [
                    'label' => 'Leads',
                    'data' => $counts,
                ],
            ],
            'labels' => array_values($statuses),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
