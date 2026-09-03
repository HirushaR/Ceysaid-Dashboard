<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = now();
        foreach ([
            ['receivables.view', 'View Receivables', 'receivables', 'View outstanding customer balances'],
            ['payments.view', 'View Payment Register', 'payments', 'View incoming and outgoing payment records'],
        ] as [$name, $display, $resource, $description]) {
            DB::table('permissions')->updateOrInsert(['name' => $name], [
                'display_name' => $display, 'resource' => $resource, 'action' => 'view',
                'description' => $description, 'updated_at' => $now, 'created_at' => $now,
            ]);
        }

        $groups = DB::table('permission_groups')->whereIn('name', ['finance_management', 'account_access'])->pluck('id');
        $permissions = DB::table('permissions')->whereIn('name', ['receivables.view', 'payments.view'])->pluck('id');
        foreach ($groups as $groupId) {
            foreach ($permissions as $permissionId) {
                DB::table('permission_group_permissions')->updateOrInsert(
                    ['permission_group_id' => $groupId, 'permission_id' => $permissionId],
                    ['created_at' => $now, 'updated_at' => $now],
                );
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('name', ['receivables.view', 'payments.view'])->pluck('id');
        DB::table('permission_group_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
