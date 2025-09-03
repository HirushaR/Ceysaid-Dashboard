<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Lead;
use Carbon\Carbon;

class LeadsChartWidget extends ChartWidget
{
    public function getHeading(): string
    {
        return 'Leads by Date';
    }

    protected static ?string $maxHeight = '300px';
    protected static ?string $pollingInterval = null;

    protected function getData(): array
    {
        $user = auth()->user();
        $query = Lead::query();

        // Apply user-based filtering
        if ($user && !$user->isAdmin()) {
            if ($user->isSales()) {
                $query->where('assigned_to', $user->id);
            } elseif ($user->isOperation()) {
                $query->where('assigned_operator', $user->id);
            }
        }

        // Get leads for last 7 days
        $startDate = Carbon::now()->subDays(6)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        $query->whereBetween('created_at', [$startDate, $endDate]);

        // Get leads grouped by date
        $leadsData = $query
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Generate date labels and data
        $dates = [];
        $counts = [];
        
        $currentDate = Carbon::parse($startDate);
        
        while ($currentDate->lte($endDate)) {
            $dateStr = $currentDate->format('Y-m-d');
            $dateLabel = $currentDate->format('M j');
            
            $dates[] = $dateLabel;
            $counts[] = $leadsData->get($dateStr)?->count ?? 0;
            
            $currentDate->addDay();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Leads',
                    'data' => $counts,
                    'backgroundColor' => '#3b82f6',
                    'borderColor' => '#1d4ed8',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $dates,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}


