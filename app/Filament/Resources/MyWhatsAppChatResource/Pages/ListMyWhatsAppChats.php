<?php

namespace App\Filament\Resources\MyWhatsAppChatResource\Pages;

use App\Filament\Resources\Concerns\OrdersWhatsAppConversationsByRecentActivity;
use App\Filament\Resources\MyWhatsAppChatResource;
use Filament\Resources\Pages\ListRecords;

class ListMyWhatsAppChats extends ListRecords
{
    use OrdersWhatsAppConversationsByRecentActivity;

    protected static string $resource = MyWhatsAppChatResource::class;

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
