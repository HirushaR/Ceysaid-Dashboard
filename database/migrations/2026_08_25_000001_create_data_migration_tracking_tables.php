<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_migration_runs', function (Blueprint $table) {
            $table->id();
            $table->string('migration_key', 120)->index();
            $table->uuid('run_uuid')->unique();
            $table->string('mode', 20);
            $table->string('status', 20)->index();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('source_min_id')->nullable();
            $table->unsignedBigInteger('source_max_id')->nullable();
            $table->unsignedBigInteger('last_processed_id')->nullable();
            $table->unsignedBigInteger('processed_count')->default(0);
            $table->unsignedBigInteger('created_count')->default(0);
            $table->unsignedBigInteger('updated_count')->default(0);
            $table->unsignedBigInteger('skipped_count')->default(0);
            $table->unsignedBigInteger('error_count')->default(0);
            $table->json('options')->nullable();
            $table->json('summary')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });

        Schema::create('data_migration_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('data_migration_runs')->cascadeOnDelete();
            $table->string('issue_code', 100);
            $table->string('severity', 20)->index();
            $table->string('source_type', 100);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('message', 500);
            $table->json('details')->nullable();
            $table->string('resolution_status', 20)->default('open')->index();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
            $table->index(['run_id', 'issue_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_migration_issues');
        Schema::dropIfExists('data_migration_runs');
    }
};
