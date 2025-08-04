<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\SalesMetricsWidget;
use App\Filament\Widgets\LeadsByStatusWidget;
use App\Filament\Widgets\RevenueProfitTrendWidget;
use App\Filament\Widgets\SalesKPIsWidget;
use App\Filament\Widgets\LeadMetricsWidget;
use App\Filament\Widgets\AllLeadMetricsWidget;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Home';

    protected function getHeaderWidgets(): array
    {
        $user = auth()->user();
        
        if ($user && $user->isSales()) {
            return [
                SalesMetricsWidget::class,
            ];
        }
        
        return [
            AllLeadMetricsWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        $user = auth()->user();
        
        if ($user && $user->isSales()) {
            return [
                SalesKPIsWidget::class,
                LeadsByStatusWidget::class,
                RevenueProfitTrendWidget::class,
            ];
        }
        
        return [];
    }
} 