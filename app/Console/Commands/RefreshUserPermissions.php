<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\PermissionGroup;

class RefreshUserPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:refresh-permissions {--user= : Specific user ID to refresh}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh user permissions from their assigned permission groups';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->option('user');
        
        if ($userId) {
            $users = User::where('id', $userId)->get();
        } else {
            $users = User::all();
        }

        $this->info("Refreshing permissions for " . $users->count() . " user(s)...");

        foreach ($users as $user) {
            $this->line("Processing user: {$user->name} (ID: {$user->id})");
            
            // Get all permissions from user's permission groups
            $groupPermissions = collect();
            foreach ($user->permissionGroups as $group) {
                $groupPermissions = $groupPermissions->merge($group->permissions);
            }
            
            // Remove duplicates and get permission IDs
            $permissionIds = $groupPermissions->unique('id')->pluck('id');
            
            // Sync permissions to user
            $user->permissions()->sync($permissionIds);
            
            $this->info("  - Synced " . $permissionIds->count() . " permissions");
        }

        $this->info("Permission refresh completed successfully!");
    }
} 