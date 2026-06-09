<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_message_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_message_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->timestamp('status_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index(['whatsapp_message_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_message_statuses');
    }
};
