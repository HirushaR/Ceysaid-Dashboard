<?php

namespace App\Filament\Resources\WhatsAppInboxResource\Pages;

use App\Filament\Resources\WhatsAppInboxResource;
use Filament\Resources\Pages\ListRecords;

class ListWhatsAppConversations extends ListRecords
{
    protected static string $resource = WhatsAppInboxResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
