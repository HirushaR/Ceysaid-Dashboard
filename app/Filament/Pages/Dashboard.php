<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\LeadsByStatusWidget;
use App\Filament\Widgets\RevenueProfitTrendWidget;
use App\Filament\Widgets\SalesKPIsWidget;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Home';

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getFooterWidgets(): array
    {
        $user = auth()->user();
        
        if ($user && $user->isSales()) {
            return [
                // SalesKPIsWidget::class,
                // LeadsByStatusWidget::class,
                // RevenueProfitTrendWidget::class,
            ];
        }
        
        return [];
    }
} 