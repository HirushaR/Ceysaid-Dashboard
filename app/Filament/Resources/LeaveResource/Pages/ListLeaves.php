<?php

namespace App\Filament\Resources\LeaveResource\Pages;

use App\Filament\Resources\LeaveResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeaves extends ListRecords
{
    protected static string $resource = LeaveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTitle(): string 
    {
        // Get active filters to determine title
        $filters = request()->get('tableFilters', []);
        $statusFilter = $filters['status']['value'] ?? 'pending';
        
        return match($statusFilter) {
            'all' => '📊 All Leave Requests',
            'approved' => '✅ Approved Leave Requests',
            'rejected' => '❌ Rejected Leave Requests', 
            'cancelled' => '🚫 Cancelled Leave Requests',
            default => '⏳ Pending Leave Approvals'
        };
    }
}
