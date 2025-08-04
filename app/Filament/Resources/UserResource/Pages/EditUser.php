<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\PermissionGroup;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        $user = auth()->user();
        return $user && ($user->hasPermission('users.edit') || $user->isHR() || $user->isAdmin());
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        // Handle permission group assignment
        $permissionGroups = $this->form->getState()['permission_groups'] ?? [];
        
        // Debug information
        \Log::info('Permission groups from form:', $permissionGroups);
        
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
                
                \Log::info('Permission IDs to sync:', $permissionIds);
                
                // Sync individual permissions (this will replace existing permissions)
                $this->record->permissions()->sync($permissionIds);
                
                $permissionCount = count($permissionIds);
                
                Notification::make()
                    ->success()
                    ->title('Permission groups assigned successfully')
                    ->body("User permissions have been updated. User now has {$permissionCount} permissions.")
                    ->send();
                    
            } catch (\Exception $e) {
                \Log::error('Error assigning permissions:', ['error' => $e->getMessage()]);
                
                Notification::make()
                    ->danger()
                    ->title('Error assigning permissions')
                    ->body('There was an error assigning the permission groups: ' . $e->getMessage())
                    ->send();
            }
        } else {
            \Log::info('No permission groups selected or empty array');
        }
    }
}
