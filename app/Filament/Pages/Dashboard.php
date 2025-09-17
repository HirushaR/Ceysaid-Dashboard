<?php

namespace App\Filament\Pages;
use Filament\Panel;

use Filament\Pages\Dashboard as BaseDashboard;


class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Home';




    protected function getHeaderWidgets(): array
    {
        $user = auth()->user();
        
        $widgets = [
            // \App\Filament\Widgets\LeadsByStatusWidget::class,
            // \App\Filament\Widgets\LeadsByDateWidget::class,
        ];
        
        // Add call center stats widget for call center users and admins
        // if ($user && ($user->isCallCenter())) {
        //     $widgets[] = \App\Filament\Widgets\CallCenterStatsWidget::class;
        // }
        
        return $widgets;
    }

    protected function getFooterWidgets(): array
    {
        $user = auth()->user();
        
        if ($user && ($user->isSales() || $user->isAdmin())) {
            return [               
            ];
        }
        
        return [];
    }
} 