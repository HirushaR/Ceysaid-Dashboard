<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use App\Enums\LeadStatus;

class CreateLead extends CreateRecord
{
    protected static string $resource = LeadResource::class;

    public static function canCreate(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        // Allow admin, marketing, and sales users to create leads
        return $user->isAdmin() || $user->isMarketing() || $user->isSales();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        
        // If the user is a sales user, automatically assign the lead to them
        if ($user && $user->isSales()) {
            $data['assigned_to'] = $user->id;
            $data['status'] = LeadStatus::ASSIGNED_TO_SALES->value;
        }
        
        // Ensure created_by is set
        if (!isset($data['created_by'])) {
            $data['created_by'] = $user->id;
        }
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
