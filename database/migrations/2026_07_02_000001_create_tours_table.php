<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tours', function (Blueprint $table) {
            $table->id();
            $table->string('tour_code')->unique();
            $table->string('name');
            $table->date('departure_date');
            $table->date('return_date')->nullable();
            $table->decimal('package_price', 14, 2)->default(0);
            $table->string('currency', 3)->default('LKR');
            $table->unsignedInteger('seat_capacity')->nullable();
            $table->string('status')->default('open');
            $table->decimal('estimated_vendor_cost', 14, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('departure_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tours');
    }
};
