<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->foreignId('visa_assigned_to')
                ->nullable()
                ->after('assigned_operator')
                ->constrained('users')
                ->nullOnDelete();
        });

        $now = now();
        foreach ([
            ['visa.view', 'View Assigned Visa Leads', 'view', 'Can access visa leads assigned to the user'],
            ['visa.process', 'Process Assigned Visa Leads', 'process', 'Can update the status of visa leads assigned to the user'],
            ['visa.assign', 'Assign Visa Leads', 'assign', 'Can see the full visa queue and assign visa leads to team members'],
        ] as [$name, $displayName, $action, $description]) {
            DB::table('permissions')->updateOrInsert(['name' => $name], [
                'display_name' => $displayName,
                'resource' => 'visa',
                'action' => $action,
                'description' => $description,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('permission_groups')->updateOrInsert(['name' => 'visa_processing'], [
            'display_name' => 'Visa Processing',
            'description' => 'Access for dedicated operation team members processing assigned visa leads',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $groupId = DB::table('permission_groups')->where('name', 'visa_processing')->value('id');
        $permissionIds = DB::table('permissions')->whereIn('name', ['visa.view', 'visa.process'])->pluck('id');
        foreach ($permissionIds as $permissionId) {
            DB::table('permission_group_permissions')->updateOrInsert(
                ['permission_group_id' => $groupId, 'permission_id' => $permissionId],
                ['created_at' => $now, 'updated_at' => $now],
            );
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')->whereIn('name', ['visa.view', 'visa.process', 'visa.assign'])->pluck('id');
        $groupId = DB::table('permission_groups')->where('name', 'visa_processing')->value('id');
        if ($groupId) {
            DB::table('user_permission_groups')->where('permission_group_id', $groupId)->delete();
            DB::table('permission_group_permissions')->where('permission_group_id', $groupId)->delete();
            DB::table('permission_groups')->where('id', $groupId)->delete();
        }
        DB::table('user_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permission_group_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();

        Schema::table('leads', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('visa_assigned_to');
        });
    }
};
