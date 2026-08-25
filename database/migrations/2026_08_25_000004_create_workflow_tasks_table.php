<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->restrictOnDelete();
            $table->string('task_type', 50);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 24)->default('open');
            $table->string('priority', 16)->default('normal');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('owner_role', 32)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('outcome_code', 50)->nullable();
            $table->text('outcome_notes')->nullable();
            $table->string('automation_key', 120)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['lead_id', 'automation_key']);
            $table->index(['owner_id', 'status', 'due_at']);
            $table->index(['owner_role', 'status', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_tasks');
    }
};
