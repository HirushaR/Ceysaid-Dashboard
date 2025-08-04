<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\PermissionGroup;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        $user = auth()->user();
        return $user && ($user->hasPermission('users.create') || $user->isHR() || $user->isAdmin());
    }

    protected function afterCreate(): void
    {
        // Handle permission group assignment for new users
        $permissionGroups = $this->form->getState()['permission_groups'] ?? [];
        
        // Debug information
        \Log::info('Permission groups from form (create):', $permissionGroups);
        
        if (is_array($permissionGroups) && !empty($permissionGroups)) {
            try {
                // Sync permission groups
                $this->record->permissionGroups()->sync($permissionGroups);
                
                // Get all permissions from the selected groups
                $permissionIds = PermissionGroup::whereIn('id', $permissionGroups)
                    ->with('permissions')
                    ->get()
                    ->flatMap(function ($group) {
                        return $group->permissions->pluck('id');
                    })
                    ->unique()
                    ->toArray();
                
                \Log::info('Permission IDs to sync (create):', $permissionIds);
                
                // Sync individual permissions
                $this->record->permissions()->sync($permissionIds);
                
                $permissionCount = count($permissionIds);
                
                Notification::make()
                    ->success()
                    ->title('User created successfully')
                    ->body("User has been created with {$permissionCount} permissions from the selected permission groups.")
                    ->send();
                    
            } catch (\Exception $e) {
                \Log::error('Error assigning permissions (create):', ['error' => $e->getMessage()]);
                
                Notification::make()
                    ->danger()
                    ->title('Error assigning permissions')
                    ->body('User was created but there was an error assigning permission groups: ' . $e->getMessage())
                    ->send();
            }
        } else {
            \Log::info('No permission groups selected or empty array (create)');
        }
    }
}
