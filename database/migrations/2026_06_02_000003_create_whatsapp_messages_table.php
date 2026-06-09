<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_conversation_id')->constrained()->cascadeOnDelete();
            $table->string('wamid')->unique();
            $table->enum('direction', ['inbound', 'outbound']);
            $table->string('type')->default('text');
            $table->text('body')->nullable();
            $table->string('media_id')->nullable();
            $table->string('media_mime_type')->nullable();
            $table->string('media_path')->nullable();
            $table->string('status')->default('received');
            $table->foreignId('sent_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['whatsapp_conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
