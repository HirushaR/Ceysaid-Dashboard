<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('air_ticket_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vendor_bill_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('booking_reference');
            $table->string('airline');
            $table->dateTime('time_limit');
            $table->decimal('amount', 15, 2);
            $table->string('status')->default('pending_approval')->index();
            $table->foreignId('queued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('queued_at');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->text('decision_notes')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('issued_at')->nullable();
            $table->string('ticket_number')->nullable();
            $table->text('issue_notes')->nullable();
            $table->timestamps();
            $table->index('time_limit');
        });

        $now = now();
        foreach ([
            ['air_tickets.view', 'View Air Tickets', 'view', 'Can view air ticket queues'],
            ['air_tickets.queue', 'Queue Air Tickets', 'queue', 'Can queue visible vendor bills for ticket issuing'],
        ] as [$name, $displayName, $action, $description]) {
            DB::table('permissions')->updateOrInsert(['name' => $name], [
                'display_name' => $displayName,
                'resource' => 'air_tickets',
                'action' => $action,
                'description' => $description,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')->whereIn('name', ['air_tickets.view', 'air_tickets.queue'])->pluck('id');
        DB::table('user_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permission_group_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        Schema::dropIfExists('air_ticket_requests');
    }
};
