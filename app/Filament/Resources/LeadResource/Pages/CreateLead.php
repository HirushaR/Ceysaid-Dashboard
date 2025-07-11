<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateLead extends CreateRecord
{
    protected static string $resource = LeadResource::class;

    public static function canCreate(): bool
    {
        $user = Auth::user();
        return $user && ($user->isMarketing() || $user->isAdmin());
    }
}
