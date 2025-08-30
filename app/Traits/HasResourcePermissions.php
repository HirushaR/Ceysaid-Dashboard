<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Resource;

trait HasResourcePermissions
{
    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        
        // Admin users can view all resources
        if ($user->isAdmin()) return true;
        
        $resourceName = static::getResourceName();
        return $user->canViewResource($resourceName);
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        
        // Admin users can create all resources
        if ($user->isAdmin()) return true;
        
        $resourceName = static::getResourceName();
        return $user->canCreateResource($resourceName);
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        
        // Admin users can edit all resources
        if ($user->isAdmin()) return true;
        
        $resourceName = static::getResourceName();
        return $user->canEditResource($resourceName);
    }

    public static function canDelete(Model $record): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        
        // Admin users can delete all resources
        if ($user->isAdmin()) return true;
        
        $resourceName = static::getResourceName();
        return $user->canDeleteResource($resourceName);
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        
        // Admin users can view all resources
        if ($user->isAdmin()) return true;
        
        $resourceName = static::getResourceName();
        return $user->canViewResource($resourceName);
    }

    protected static function getResourceName(): string
    {
        $className = class_basename(static::class);
        $resourceName = strtolower(str_replace('Resource', '', $className));
        
        // Map singular resource names to their plural forms for permissions
        $resourceMap = [
            'lead' => 'leads',
            'customer' => 'customers',
            'invoice' => 'invoices',
            'vendorbill' => 'vendor_bills',
            'leaverequest' => 'leave_requests',
            'leave' => 'leaves',
            'user' => 'users',
            'permission' => 'permissions',
            'permissiongroup' => 'permission_groups',
            'confirmlead' => 'confirm_leads',
            'documentcompletelead' => 'document_complete_leads',
            'allsalesdashboard' => 'all_sales_dashboards',
            'mysalesdashboard' => 'my_sales_dashboards',
            'myoperationleaddashboard' => 'my_operation_lead_dashboards',
        ];
        
        return $resourceMap[$resourceName] ?? $resourceName;
    }
} 