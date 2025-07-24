<?php

namespace App\Filament\Resources\DocumentCompleteLeadResource\Pages;

use App\Filament\Resources\DocumentCompleteLeadResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDocumentCompleteLeads extends ListRecords
{
    protected static string $resource = DocumentCompleteLeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action since these are filtered leads
        ];
    }
} 