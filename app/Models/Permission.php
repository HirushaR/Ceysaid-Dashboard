<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'resource',
        'action',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_permissions')
                    ->withPivot(['granted_by', 'granted_at'])
                    ->withTimestamps();
    }

    public function permissionGroups()
    {
        return $this->belongsToMany(PermissionGroup::class, 'permission_group_permissions');
    }

    public function scopeByResource($query, $resource)
    {
        return $query->where('resource', $resource);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }
} 