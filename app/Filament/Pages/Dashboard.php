<?php

namespace App\Filament\Pages;
use Filament\Panel;

use Filament\Pages\Dashboard as BaseDashboard;


class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Home';




    protected function getHeaderWidgets(): array
    {
        return [
           
        ];
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