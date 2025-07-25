<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Filament\Actions;
use Filament\Actions\Action;

class QuickLeaveRequestWidget extends Widget
{
    protected static string $view = 'filament.widgets.quick-leave-request-widget';

    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        return [
            'createAction' => $this->createAction(),
            'viewAction' => $this->viewAction(),
        ];
    }

    public function createAction(): Action
    {
        return Action::make('create')
            ->label('🎯 Submit New Leave Request')
            ->color('success')
            ->size('lg')
            ->icon('heroicon-o-plus-circle')
            ->url(route('filament.admin.resources.leave-requests.create'))
            ->extraAttributes([
                'class' => 'w-full justify-center'
            ]);
    }

    public function viewAction(): Action
    {
        return Action::make('view')
            ->label('📋 View My Leave Requests')
            ->color('primary')
            ->size('lg')
            ->icon('heroicon-o-eye')
            ->url(route('filament.admin.resources.leave-requests.index'))
            ->extraAttributes([
                'class' => 'w-full justify-center'
            ]);
    }

    public static function canView(): bool
    {
        return auth()->check();
    }
}
