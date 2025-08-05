<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
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
                // Minimal Theme Color Palette
                'primary' => [
                    50 => '239, 246, 255',
                    100 => '219, 234, 254',
                    200 => '191, 219, 254',
                    300 => '147, 197, 253',
                    400 => '96, 165, 250',
                    500 => '59, 130, 246',
                    600 => '37, 99, 235',
                    700 => '29, 78, 216',
                    800 => '30, 64, 175',
                    900 => '30, 58, 138',
                    950 => '23, 37, 84',
                ],
                'success' => [
                    50 => '240, 253, 244',
                    100 => '220, 252, 231',
                    200 => '187, 247, 208',
                    300 => '134, 239, 172',
                    400 => '74, 222, 128',
                    500 => '34, 197, 94',
                    600 => '22, 163, 74',
                    700 => '21, 128, 61',
                    800 => '22, 101, 52',
                    900 => '20, 83, 45',
                    950 => '5, 46, 22',
                ],
                'warning' => [
                    50 => '255, 251, 235',
                    100 => '254, 243, 199',
                    200 => '253, 230, 138',
                    300 => '252, 211, 77',
                    400 => '251, 191, 36',
                    500 => '245, 158, 11',
                    600 => '217, 119, 6',
                    700 => '180, 83, 9',
                    800 => '146, 64, 14',
                    900 => '120, 53, 15',
                    950 => '69, 26, 3',
                ],
                'danger' => [
                    50 => '254, 242, 242',
                    100 => '254, 226, 226',
                    200 => '254, 202, 202',
                    300 => '252, 165, 165',
                    400 => '248, 113, 113',
                    500 => '239, 68, 68',
                    600 => '220, 38, 38',
                    700 => '185, 28, 28',
                    800 => '153, 27, 27',
                    900 => '127, 29, 29',
                    950 => '69, 10, 10',
                ],
                'info' => [
                    50 => '240, 249, 255',
                    100 => '224, 242, 254',
                    200 => '186, 230, 253',
                    300 => '125, 211, 252',
                    400 => '56, 189, 248',
                    500 => '14, 165, 233',
                    600 => '2, 132, 199',
                    700 => '3, 105, 161',
                    800 => '7, 89, 133',
                    900 => '12, 74, 110',
                    950 => '8, 47, 73',
                ],
                'gray' => [
                    50 => '248, 250, 252',
                    100 => '241, 245, 249',
                    200 => '226, 232, 240',
                    300 => '203, 213, 225',
                    400 => '148, 163, 184',
                    500 => '100, 116, 139',
                    600 => '71, 85, 105',
                    700 => '51, 65, 85',
                    800 => '30, 41, 59',
                    900 => '15, 23, 42',
                    950 => '2, 6, 23',
                ],
            ])
            ->font('Inter')
            ->brandName('TravelSync')
            ->favicon('/favicon.ico')
            ->darkMode(true)
            ->sidebarCollapsibleOnDesktop()
            ->renderHook(
                'panels::styles.before',
                fn () => '<link rel="stylesheet" href="' . asset('build/assets/minimal-theme-BBFZb6P1.css') . '">'
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->widgets([
                \App\Filament\Widgets\LeadsByStatusWidget::class,
                \App\Filament\Widgets\RevenueProfitTrendWidget::class,
                \App\Filament\Widgets\SalesKPIsWidget::class,
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
