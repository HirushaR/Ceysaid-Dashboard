<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->boolean('is_other_lead')->default(false)->after('is_cruise_lead');
            $table->string('other_lead_status', 32)->nullable()->after('is_other_lead');
            $table->text('ticket_details')->nullable()->after('other_lead_status');
            $table->text('hotel_details')->nullable()->after('ticket_details');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['is_other_lead', 'other_lead_status', 'ticket_details', 'hotel_details']);
        });
    }
};
