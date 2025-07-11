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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('assigned_operator')->nullable();
            $table->string('status')->default('new');
            $table->string('platform')->nullable();
            $table->string('destination')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->json('tour_details')->nullable();
            $table->json('attachments')->nullable();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
