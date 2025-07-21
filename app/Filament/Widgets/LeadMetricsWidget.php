<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Enums\LeadStatus;

class LeadMetricsWidget extends ChartWidget
{
    protected static ?string $heading = 'Leads by Status';

    protected function getData(): array
    {
        $statuses = LeadStatus::cases();
        $counts = [];
        foreach ($statuses as $status) {
            $counts[] = \App\Models\Lead::where('status', $status->value)->count();
        }
        return [
            'datasets' => [
                [
                    'label' => 'Leads',
                    'data' => $counts,
                ],
            ],
            'labels' => array_map(fn($status) => $status->label(), $statuses),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
