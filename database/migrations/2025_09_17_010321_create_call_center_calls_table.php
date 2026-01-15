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
        // Skip if table already exists (created by earlier migration)
        if (Schema::hasTable('call_center_calls')) {
            return;
        }

        Schema::create('call_center_calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_call_center_user')->constrained('users')->onDelete('cascade');
            $table->enum('call_type', ['pre_departure', 'post_arrival']);
            $table->enum('status', ['pending', 'assigned', 'called', 'not_answered', 'completed'])->default('pending');
            $table->text('call_notes')->nullable();
            $table->integer('call_attempts')->default(0);
            $table->timestamp('last_call_attempt')->nullable();
            $table->json('call_checklist_completed')->nullable();
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['lead_id', 'call_type']);
            $table->index(['assigned_call_center_user', 'status']);
            $table->index(['status', 'call_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_center_calls');
    }
};
