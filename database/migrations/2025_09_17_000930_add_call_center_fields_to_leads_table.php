<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('call_center_status')->default('pending')->after('status');
            $table->unsignedBigInteger('assigned_call_center_user')->nullable()->after('assigned_operator');
            $table->text('call_notes')->nullable()->after('assigned_call_center_user');
            $table->integer('call_attempts')->default(0)->after('call_notes');
            $table->timestamp('last_call_attempt')->nullable()->after('call_attempts');
            $table->json('call_checklist_completed')->nullable()->after('last_call_attempt');
            
            // Add foreign key constraint
            $table->foreign('assigned_call_center_user')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['assigned_call_center_user']);
            $table->dropColumn([
                'call_center_status',
                'assigned_call_center_user',
                'call_notes',
                'call_attempts',
                'last_call_attempt',
                'call_checklist_completed'
            ]);
        });
    }
};
