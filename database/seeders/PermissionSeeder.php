<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\PermissionGroup;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Lead Management
            ['name' => 'leads.view', 'display_name' => 'View Leads', 'resource' => 'leads', 'action' => 'view', 'description' => 'Can view lead information'],
            ['name' => 'leads.create', 'display_name' => 'Create Leads', 'resource' => 'leads', 'action' => 'create', 'description' => 'Can create new leads'],
            ['name' => 'leads.edit', 'display_name' => 'Edit Leads', 'resource' => 'leads', 'action' => 'edit', 'description' => 'Can edit existing leads'],
            ['name' => 'leads.delete', 'display_name' => 'Delete Leads', 'resource' => 'leads', 'action' => 'delete', 'description' => 'Can delete leads'],
            
            // Customer Management
            ['name' => 'customers.view', 'display_name' => 'View Customers', 'resource' => 'customers', 'action' => 'view', 'description' => 'Can view customer information'],
            ['name' => 'customers.create', 'display_name' => 'Create Customers', 'resource' => 'customers', 'action' => 'create', 'description' => 'Can create new customers'],
            ['name' => 'customers.edit', 'display_name' => 'Edit Customers', 'resource' => 'customers', 'action' => 'edit', 'description' => 'Can edit existing customers'],
            ['name' => 'customers.delete', 'display_name' => 'Delete Customers', 'resource' => 'customers', 'action' => 'delete', 'description' => 'Can delete customers'],
            
            // Invoice Management
            ['name' => 'invoices.view', 'display_name' => 'View Invoices', 'resource' => 'invoices', 'action' => 'view', 'description' => 'Can view invoice information'],
            ['name' => 'invoices.create', 'display_name' => 'Create Invoices', 'resource' => 'invoices', 'action' => 'create', 'description' => 'Can create new invoices'],
            ['name' => 'invoices.edit', 'display_name' => 'Edit Invoices', 'resource' => 'invoices', 'action' => 'edit', 'description' => 'Can edit existing invoices'],
            ['name' => 'invoices.delete', 'display_name' => 'Delete Invoices', 'resource' => 'invoices', 'action' => 'delete', 'description' => 'Can delete invoices'],
            
            // Vendor Bill Management
            ['name' => 'vendor_bills.view', 'display_name' => 'View Vendor Bills', 'resource' => 'vendor_bills', 'action' => 'view', 'description' => 'Can view vendor bill information'],
            ['name' => 'vendor_bills.create', 'display_name' => 'Create Vendor Bills', 'resource' => 'vendor_bills', 'action' => 'create', 'description' => 'Can create new vendor bills'],
            ['name' => 'vendor_bills.edit', 'display_name' => 'Edit Vendor Bills', 'resource' => 'vendor_bills', 'action' => 'edit', 'description' => 'Can edit existing vendor bills'],
            ['name' => 'vendor_bills.delete', 'display_name' => 'Delete Vendor Bills', 'resource' => 'vendor_bills', 'action' => 'delete', 'description' => 'Can delete vendor bills'],
            
            // User Management
            ['name' => 'users.view', 'display_name' => 'View Users', 'resource' => 'users', 'action' => 'view', 'description' => 'Can view user information'],
            ['name' => 'users.create', 'display_name' => 'Create Users', 'resource' => 'users', 'action' => 'create', 'description' => 'Can create new users'],
            ['name' => 'users.edit', 'display_name' => 'Edit Users', 'resource' => 'users', 'action' => 'edit', 'description' => 'Can edit existing users'],
            ['name' => 'users.delete', 'display_name' => 'Delete Users', 'resource' => 'users', 'action' => 'delete', 'description' => 'Can delete users'],
            ['name' => 'users.manage_permissions', 'display_name' => 'Manage User Permissions', 'resource' => 'users', 'action' => 'manage_permissions', 'description' => 'Can manage user permissions'],
            
            // Leave Management
            ['name' => 'leaves.view', 'display_name' => 'View Leaves', 'resource' => 'leaves', 'action' => 'view', 'description' => 'Can view leave information'],
            ['name' => 'leaves.create', 'display_name' => 'Create Leaves', 'resource' => 'leaves', 'action' => 'create', 'description' => 'Can create new leave requests'],
            ['name' => 'leaves.edit', 'display_name' => 'Edit Leaves', 'resource' => 'leaves', 'action' => 'edit', 'description' => 'Can edit existing leave requests'],
            ['name' => 'leaves.delete', 'display_name' => 'Delete Leaves', 'resource' => 'leaves', 'action' => 'delete', 'description' => 'Can delete leave requests'],
            ['name' => 'leaves.approve', 'display_name' => 'Approve Leaves', 'resource' => 'leaves', 'action' => 'approve', 'description' => 'Can approve or reject leave requests'],
            
            // Dashboard Access
            ['name' => 'dashboard.all_leads', 'display_name' => 'All Leads Dashboard', 'resource' => 'dashboard', 'action' => 'all_leads', 'description' => 'Can access all leads dashboard'],
            ['name' => 'dashboard.my_sales', 'display_name' => 'My Sales Dashboard', 'resource' => 'dashboard', 'action' => 'my_sales', 'description' => 'Can access personal sales dashboard'],
            ['name' => 'dashboard.my_operation', 'display_name' => 'My Operation Dashboard', 'resource' => 'dashboard', 'action' => 'my_operation', 'description' => 'Can access personal operation dashboard'],
            ['name' => 'dashboard.visa_leads', 'display_name' => 'Visa Leads Dashboard', 'resource' => 'dashboard', 'action' => 'visa_leads', 'description' => 'Can access visa leads dashboard'],
            ['name' => 'dashboard.confirm_leads', 'display_name' => 'Confirm Leads Dashboard', 'resource' => 'dashboard', 'action' => 'confirm_leads', 'description' => 'Can access confirm leads dashboard'],
            
            // Permission Management
            ['name' => 'permissions.view', 'display_name' => 'View Permissions', 'resource' => 'permissions', 'action' => 'view', 'description' => 'Can view permission information'],
            ['name' => 'permissions.create', 'display_name' => 'Create Permissions', 'resource' => 'permissions', 'action' => 'create', 'description' => 'Can create new permissions'],
            ['name' => 'permissions.edit', 'display_name' => 'Edit Permissions', 'resource' => 'permissions', 'action' => 'edit', 'description' => 'Can edit existing permissions'],
            ['name' => 'permissions.delete', 'display_name' => 'Delete Permissions', 'resource' => 'permissions', 'action' => 'delete', 'description' => 'Can delete permissions'],
            
            // Call Center Management
            ['name' => 'call_center.all_arrival.view', 'display_name' => 'View All Arrival', 'resource' => 'call_center', 'action' => 'all_arrival_view', 'description' => 'Can view all arrival leads'],
            ['name' => 'call_center.all_departure.view', 'display_name' => 'View All Departure', 'resource' => 'call_center', 'action' => 'all_departure_view', 'description' => 'Can view all departure leads'],
            ['name' => 'call_center.my_assigned.view', 'display_name' => 'View My Assigned Leads', 'resource' => 'call_center', 'action' => 'my_assigned_view', 'description' => 'Can view assigned leads'],
            ['name' => 'call_center.leads.assign', 'display_name' => 'Assign Leads', 'resource' => 'call_center', 'action' => 'leads_assign', 'description' => 'Can assign leads to call center users'],
            ['name' => 'call_center.leads.call', 'display_name' => 'Make Calls', 'resource' => 'call_center', 'action' => 'leads_call', 'description' => 'Can make calls and update call status'],
            ['name' => 'call_center.leads.update_status', 'display_name' => 'Update Call Status', 'resource' => 'call_center', 'action' => 'leads_update_status', 'description' => 'Can update call center lead status'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission['name']], $permission);
        }

        // Create permission groups
        $permissionGroups = [
            [
                'name' => 'lead_management',
                'display_name' => 'Lead Management',
                'description' => 'Full access to lead management features',
                'permissions' => ['leads.view', 'leads.create', 'leads.edit', 'leads.delete']
            ],
            [
                'name' => 'customer_management',
                'display_name' => 'Customer Management',
                'description' => 'Full access to customer management features',
                'permissions' => ['customers.view', 'customers.create', 'customers.edit', 'customers.delete']
            ],
            [
                'name' => 'finance_management',
                'display_name' => 'Finance Management',
                'description' => 'Full access to finance management features',
                'permissions' => [
                    'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.delete',
                    'vendor_bills.view', 'vendor_bills.create', 'vendor_bills.edit', 'vendor_bills.delete'
                ]
            ],
            [
                'name' => 'hr_management',
                'display_name' => 'HR Management',
                'description' => 'Full access to HR management features',
                'permissions' => [
                    'users.view', 'users.create', 'users.edit', 'users.delete', 'users.manage_permissions',
                    'leaves.view', 'leaves.create', 'leaves.edit', 'leaves.delete', 'leaves.approve'
                ]
            ],
            [
                'name' => 'sales_access',
                'display_name' => 'Sales Access',
                'description' => 'Access for sales team members',
                'permissions' => [
                    'leads.view', 'leads.create', 'leads.edit',
                    'invoices.view', 'invoices.create', 'invoices.edit',
                    'vendor_bills.view', 'vendor_bills.create', 'vendor_bills.edit',
                    'dashboard.my_sales', 'dashboard.visa_leads', 'dashboard.confirm_leads'
                ]
            ],
            [
                'name' => 'operation_access',
                'display_name' => 'Operation Access',
                'description' => 'Access for operation team members',
                'permissions' => [
                    'leads.view', 'leads.edit',
                    'dashboard.all_leads', 'dashboard.my_sales', 'dashboard.my_operation', 'dashboard.visa_leads', 'dashboard.confirm_leads'
                ]
            ],
            [
                'name' => 'account_access',
                'display_name' => 'Account Access',
                'description' => 'Access for account team members',
                'permissions' => [
                    'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.delete',
                    'vendor_bills.view', 'vendor_bills.create', 'vendor_bills.edit', 'vendor_bills.delete',
                    'dashboard.all_leads'
                ]
            ],
            [
                'name' => 'call_center_access',
                'display_name' => 'Call Center Access',
                'description' => 'Full access to call center features',
                'permissions' => [
                    'call_center.all_arrival.view', 'call_center.all_departure.view', 'call_center.my_assigned.view',
                    'call_center.leads.assign', 'call_center.leads.call', 'call_center.leads.update_status',
                    'leads.view', 'leads.edit'
                ]
            ],
        ];

        foreach ($permissionGroups as $groupData) {
            $permissions = $groupData['permissions'];
            unset($groupData['permissions']);
            
            $group = PermissionGroup::firstOrCreate(['name' => $groupData['name']], $groupData);
            
            $permissionIds = Permission::whereIn('name', $permissions)->pluck('id');
            $group->permissions()->sync($permissionIds);
        }
    }
} 