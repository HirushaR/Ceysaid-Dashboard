<?php

namespace App\Filament\Pages\Concerns;

use App\Support\FeatureFlag;

trait PilotSalesPage
{
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->isSales() || $user->isAdmin()) && app(FeatureFlag::class)->enabled('ui.lead_workspace', $user);
    }
}
