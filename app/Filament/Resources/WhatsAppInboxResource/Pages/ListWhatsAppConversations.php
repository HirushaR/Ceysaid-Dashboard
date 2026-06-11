<?php

namespace App\Filament\Resources\WhatsAppInboxResource\Pages;

use App\Filament\Resources\WhatsAppInboxResource;
use Filament\Resources\Pages\ListRecords;

class ListWhatsAppConversations extends ListRecords
{
    protected static string $resource = WhatsAppInboxResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->tableSortColumn = null;
        $this->tableSortDirection = null;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
