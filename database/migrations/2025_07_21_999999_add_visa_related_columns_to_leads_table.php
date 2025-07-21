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
            $table->boolean('air_ticket')->default(false)->after('tour_details');
            $table->boolean('hotel')->default(false)->after('air_ticket');
            $table->boolean('visa')->default(false)->after('hotel');
            $table->boolean('land_package')->default(false)->after('visa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['air_ticket', 'hotel', 'visa', 'land_package']);
        });
    }
}; 