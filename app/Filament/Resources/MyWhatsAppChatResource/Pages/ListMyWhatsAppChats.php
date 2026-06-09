<?php

namespace App\Filament\Resources\MyWhatsAppChatResource\Pages;

use App\Filament\Resources\MyWhatsAppChatResource;
use Filament\Resources\Pages\ListRecords;

class ListMyWhatsAppChats extends ListRecords
{
    protected static string $resource = MyWhatsAppChatResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
