<?php

namespace App\Filament\Resources\WhatsAppInboxResource\Pages;

use App\Filament\Resources\Concerns\OrdersWhatsAppConversationsByRecentActivity;
use App\Filament\Resources\WhatsAppInboxResource;
use Filament\Resources\Pages\ListRecords;

class ListWhatsAppConversations extends ListRecords
{
    use OrdersWhatsAppConversationsByRecentActivity;

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
