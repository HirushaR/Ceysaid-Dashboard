<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('lead_type', 32)->nullable()->index();
            $table->string('lifecycle_stage', 40)->nullable()->index();
            $table->foreignId('sales_owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('operations_owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_type', 40)->nullable();
            $table->json('source_payload')->nullable();
            $table->string('waiting_reason', 40)->nullable();
            $table->timestamp('waiting_until')->nullable()->index();
            $table->timestamp('next_action_at')->nullable()->index();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('stage_entered_at')->nullable()->index();
            $table->timestamp('last_customer_activity_at')->nullable();
            $table->timestamp('last_internal_activity_at')->nullable();
            $table->unsignedInteger('lock_version')->default(1);
            $table->index(['sales_owner_id', 'lifecycle_stage', 'next_action_at'], 'leads_sales_work_index');
            $table->index(['operations_owner_id', 'lifecycle_stage', 'next_action_at'], 'leads_operations_work_index');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex('leads_sales_work_index');
            $table->dropIndex('leads_operations_work_index');
            $table->dropConstrainedForeignId('sales_owner_id');
            $table->dropConstrainedForeignId('operations_owner_id');
            $table->dropConstrainedForeignId('confirmed_by');
            $table->dropColumn(['lead_type', 'lifecycle_stage', 'source_type', 'source_payload', 'waiting_reason', 'waiting_until', 'next_action_at', 'confirmed_at', 'stage_entered_at', 'last_customer_activity_at', 'last_internal_activity_at', 'lock_version']);
        });
    }
};
