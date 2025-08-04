<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Leave;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Role helpers (keeping for backward compatibility)
    public function isMarketing(): bool
    {
        return $this->role === 'marketing';
    }
    public function isSales(): bool
    {
        return $this->role === 'sales';
    }
    public function isOperation(): bool
    {
        return $this->role === 'operation';
    }
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
    public function isAccount(): bool
    {
        return $this->role === 'account';
    }
    public function isHR(): bool
    {
        return $this->role === 'hr';
    }

    // Permission relationships
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
                    ->withPivot(['granted_by', 'granted_at'])
                    ->withTimestamps();
    }

    public function permissionGroups()
    {
        return $this->belongsToMany(PermissionGroup::class, 'user_permission_groups')
                    ->withPivot(['granted_by', 'granted_at'])
                    ->withTimestamps();
    }

    // Permission checking methods
    public function hasPermission(string $permission): bool
    {
        return $this->permissions()
            ->where('name', $permission)
            ->exists();
    }

    public function hasAnyPermission(array $permissions): bool
    {
        return $this->permissions()
            ->whereIn('name', $permissions)
            ->exists();
    }

    public function hasAllPermissions(array $permissions): bool
    {
        $userPermissions = $this->permissions()
            ->whereIn('name', $permissions)
            ->pluck('name')
            ->toArray();
        
        return count(array_intersect($permissions, $userPermissions)) === count($permissions);
    }

    // Resource-specific permission methods
    public function canViewResource(string $resource): bool
    {
        return $this->hasPermission("{$resource}.view");
    }

    public function canCreateResource(string $resource): bool
    {
        return $this->hasPermission("{$resource}.create");
    }

    public function canEditResource(string $resource): bool
    {
        return $this->hasPermission("{$resource}.edit");
    }

    public function canDeleteResource(string $resource): bool
    {
        return $this->hasPermission("{$resource}.delete");
    }

    // Get all active permissions for user
    public function getActivePermissions()
    {
        return $this->permissions()->get();
    }

    // Grant permission to user
    public function grantPermission(string $permissionName, ?int $grantedBy = null)
    {
        $permission = Permission::where('name', $permissionName)->first();
        
        if (!$permission) {
            throw new \Exception("Permission '{$permissionName}' not found");
        }

        $this->permissions()->attach($permission->id, [
            'granted_by' => $grantedBy ?? auth()->id(),
            'granted_at' => now(),
        ]);
    }

    // Revoke permission from user
    public function revokePermission(string $permissionName)
    {
        $permission = Permission::where('name', $permissionName)->first();
        
        if ($permission) {
            $this->permissions()->detach($permission->id);
        }
    }

    // Relationships
    public function leaves(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Leave::class);
    }

    public function approvedLeaves(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Leave::class, 'approved_by');
    }
}
