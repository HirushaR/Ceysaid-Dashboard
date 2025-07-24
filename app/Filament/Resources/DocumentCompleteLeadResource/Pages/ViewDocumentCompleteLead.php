<?php

namespace App\Filament\Resources\DocumentCompleteLeadResource\Pages;

use App\Filament\Resources\DocumentCompleteLeadResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewDocumentCompleteLead extends ViewRecord
{
    protected static string $resource = DocumentCompleteLeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn() => auth()->user()?->isSales() || auth()->user()?->isOperation() || auth()->user()?->isAdmin()),
        ];
    }
} 