<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_uuid')->unique();
            $table->foreignId('lead_id')->constrained()->restrictOnDelete();
            $table->string('event_type', 80);
            $table->unsignedSmallInteger('event_version')->default(1);
            $table->string('actor_type', 24);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('occurred_at');
            $table->timestamp('recorded_at');
            $table->nullableMorphs('subject');
            $table->uuid('correlation_id')->nullable()->index();
            $table->uuid('causation_event_uuid')->nullable();
            $table->string('source', 32);
            $table->string('summary', 500);
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->json('metadata')->nullable();
            $table->string('request_id', 100)->nullable();
            $table->char('ip_hash', 64)->nullable();
            $table->index(['lead_id', 'occurred_at', 'id']);
            $table->index(['event_type', 'occurred_at']);
            $table->index(['actor_id', 'occurred_at']);
        });

        Schema::create('workflow_requests', function (Blueprint $table) {
            $table->id();
            $table->string('idempotency_key', 150);
            $table->foreignId('lead_id')->constrained()->restrictOnDelete();
            $table->string('action', 80);
            $table->string('status', 20)->default('processing');
            $table->uuid('correlation_id');
            $table->json('result')->nullable();
            $table->text('failure')->nullable();
            $table->timestamps();
            $table->unique(['lead_id', 'idempotency_key']);
        });

        Schema::create('workflow_outbox', function (Blueprint $table) {
            $table->id();
            $table->uuid('message_uuid')->unique();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('correlation_id')->nullable()->index();
            $table->string('message_type', 100);
            $table->json('payload');
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('available_at')->index();
            $table->timestamp('processed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_outbox');
        Schema::dropIfExists('workflow_requests');
        Schema::dropIfExists('workflow_events');
    }
};
