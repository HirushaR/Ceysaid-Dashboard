<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit User')
                ->color('primary')
                ->authorize(fn ($record) => auth()->user() && (
                    auth()->user()->hasPermission('users.edit') || 
                    auth()->user()->isHR() || 
                    auth()->user()->isAdmin()
                ) && auth()->user()->id !== $record->id),
        ];
    }
}
