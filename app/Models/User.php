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
        'is_manager',
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
            'is_manager' => 'boolean',
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
    public function isCallCenter(): bool
    {
        return $this->role === 'call_center';
    }

    // Manager helpers
    public function isManager(): bool
    {
        return $this->is_manager === true;
    }

    public function canManageRole(string $role): bool
    {
        // Managers can only manage users with the same role
        return $this->isManager() && $this->role === $role;
    }

    public function getManageableRoles(): array
    {
        // Roles that can have managers
        return ['sales', 'call_center', 'operation', 'account', 'hr'];
    }

    public function isEligibleForManager(): bool
    {
        // Only specific roles can be managers
        return in_array($this->role, $this->getManageableRoles());
    }

    // Get team members (users with same role, excluding self)
    public function teamMembers()
    {
        if (!$this->isManager()) {
            return static::whereRaw('1 = 0'); // Return empty query
        }

        return static::where('role', $this->role)
            ->where('id', '!=', $this->id)
            ->where('is_manager', false);
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

    public function leads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Lead::class, 'assigned_to')->whereNull('archived_at');
    }

    public function operatorLeads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Lead::class, 'assigned_operator')->whereNull('archived_at');
    }

    public function callCenterCalls(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CallCenterCall::class, 'assigned_call_center_user');
    }

    // Get leads through call center calls (for call center users)
    public function callCenterLeads()
    {
        return Lead::whereHas('callCenterCalls', function ($query) {
            $query->where('assigned_call_center_user', $this->id);
        });
    }

    // Get all leads (assigned_to, assigned_operator, or via call_center_calls) based on role
    public function getAllLeads()
    {
        if ($this->isCallCenter()) {
            return $this->callCenterLeads();
        }
        if ($this->isOperation()) {
            return $this->operatorLeads();
        }
        return $this->leads();
    }

    // Check if a lead is assigned to this user (through any method)
    public function hasLeadAssigned(Lead $lead): bool
    {
        if ($this->isCallCenter()) {
            return $lead->callCenterCalls()
                ->where('assigned_call_center_user', $this->id)
                ->exists();
        }
        if ($this->isOperation()) {
            return $lead->assigned_operator === $this->id;
        }
        return $lead->assigned_to === $this->id;
    }

    public function createdLeads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Lead::class, 'created_by')->whereNull('archived_at');
    }

    /**
     * Get remaining leave balances for the current calendar year
     */
    public function getRemainingLeaves(?int $year = null): array
    {
        $service = app(\App\Services\LeaveAllocationService::class);
        return $service->getRemainingLeaves($this, $year);
    }

    /**
     * Get used leave balances for the current calendar year
     */
    public function getUsedLeaves(?int $year = null): array
    {
        $service = app(\App\Services\LeaveAllocationService::class);
        return $service->getUsedLeaves($this, $year);
    }

    /**
     * Get leave allocations
     */
    public function getLeaveAllocations(): array
    {
        $service = app(\App\Services\LeaveAllocationService::class);
        return $service->getAllocations();
    }
}
