<?php

namespace App\Filament\Resources\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait OrdersWhatsAppConversationsByRecentActivity
{
    public function getFilteredSortedTableQuery(): Builder
    {
        $query = $this->getFilteredTableQuery();

        $this->applyGroupingToTableQuery($query);

        return $query->reorder()->orderByRecentActivity();
    }
}
