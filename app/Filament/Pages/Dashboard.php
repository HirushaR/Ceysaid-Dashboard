<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
// use App\Filament\Widgets\LeadsByStatusWidget;
// use App\Filament\Widgets\RevenueProfitTrendWidget;
// use App\Filament\Widgets\SalesKPIsWidget;
// use App\Filament\Widgets\LeadsChartWidget;
// use App\Filament\Widgets\LeadsByDateBarWidget;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Home';

    protected function getHeaderWidgets(): array
    {
        return [
           // LeadsChartWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        $user = auth()->user();
        
        if ($user && ($user->isSales() || $user->isAdmin())) {
            return [
                // SalesKPIsWidget::class,
                // LeadsByStatusWidget::class,
                // RevenueProfitTrendWidget::class,
               // LeadsByDateBarWidget::class,
            ];
        }
        
        return [];
    }
} 