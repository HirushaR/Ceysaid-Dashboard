<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Permission;

class MigrateRolesToPermissions extends Command
{
    protected $signature = 'permissions:migrate-roles';
    protected $description = 'Migrate existing role-based permissions to granular permissions';

    public function handle()
    {
        $this->info('Starting role to permission migration...');

        $rolePermissionMap = [
            'admin' => [
                'leads.view', 'leads.create', 'leads.edit', 'leads.delete',
                'customers.view', 'customers.create', 'customers.edit', 'customers.delete',
                'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.delete',
                'vendor_bills.view', 'vendor_bills.create', 'vendor_bills.edit', 'vendor_bills.delete',
                'users.view', 'users.create', 'users.edit', 'users.delete', 'users.manage_permissions',
                'leaves.view', 'leaves.create', 'leaves.edit', 'leaves.delete', 'leaves.approve',
                'dashboard.all_leads', 'dashboard.my_sales', 'dashboard.my_operation', 
                'dashboard.visa_leads', 'dashboard.confirm_leads',
                'permissions.view', 'permissions.create', 'permissions.edit', 'permissions.delete',
            ],
            'hr' => [
                'users.view', 'users.create', 'users.edit', 'users.delete', 'users.manage_permissions',
                'leaves.view', 'leaves.create', 'leaves.edit', 'leaves.delete', 'leaves.approve',
                'permissions.view',
            ],
            'sales' => [
                'leads.view', 'leads.create', 'leads.edit',
                'dashboard.my_sales', 'dashboard.visa_leads', 'dashboard.confirm_leads',
            ],
            'operation' => [
                'leads.view', 'leads.edit',
                'dashboard.my_operation', 'dashboard.visa_leads', 'dashboard.confirm_leads',
            ],
            'marketing' => [
                'leads.view', 'leads.create', 'leads.edit',
            ],
            'account' => [
                'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.delete',
                'vendor_bills.view', 'vendor_bills.create', 'vendor_bills.edit', 'vendor_bills.delete',
                'dashboard.all_leads',
            ],
        ];

        $totalUsers = 0;
        $totalPermissions = 0;

        foreach ($rolePermissionMap as $role => $permissions) {
            $users = User::where('role', $role)->get();
            $permissionIds = Permission::whereIn('name', $permissions)->pluck('id');
            
            $this->info("Processing {$role} role: {$users->count()} users, " . count($permissions) . " permissions");
            
            foreach ($users as $user) {
                // Attach permissions to user
                $user->permissions()->attach($permissionIds, [
                    'granted_by' => 1, // Assuming admin user ID is 1
                    'granted_at' => now(),
                ]);
                
                $totalPermissions += count($permissionIds);
            }
            
            $totalUsers += $users->count();
        }

        $this->info("Migration completed successfully!");
        $this->info("Total users processed: {$totalUsers}");
        $this->info("Total permissions assigned: {$totalPermissions}");
        
        return Command::SUCCESS;
    }
} 