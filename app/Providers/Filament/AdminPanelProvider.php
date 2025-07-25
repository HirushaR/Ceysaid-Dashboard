<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Blue,
                // Add custom colors using different methods
                'brand' => Color::hex('#6366f1'),              // Custom hex color
                'success' => Color::Green,                     // Predefined color
                'warning' => Color::Amber,                     // Predefined color  
                'danger' => Color::Red,                        // Predefined color
                'secondary' => Color::Gray,                    // Predefined color
                'info' => Color::Sky,                          // Predefined color
                // Custom RGB color
                'accent' => Color::rgb('rgb(139, 69, 19)'),    // Brown accent
                // You can also define custom color shades
                'company' => [
                    50 => '252, 251, 246',
                    100 => '246, 244, 235', 
                    200 => '237, 233, 217',
                    300 => '224, 218, 185',
                    400 => '205, 195, 143',
                    500 => '186, 174, 108',
                    600 => '167, 156, 89',
                    700 => '140, 130, 74',
                    800 => '115, 107, 61',
                    900 => '94, 88, 50',
                    950 => '53, 50, 28',
                ],
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
                \App\Filament\Widgets\LeadMetricsWidget::class,
                \App\Filament\Widgets\QuickLeaveRequestWidget::class,
                \App\Filament\Widgets\LeaveRequestWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
