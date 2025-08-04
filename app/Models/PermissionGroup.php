<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermissionGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_group_permissions');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_permission_groups')
                    ->withPivot(['granted_by', 'granted_at'])
                    ->withTimestamps();
    }
} 