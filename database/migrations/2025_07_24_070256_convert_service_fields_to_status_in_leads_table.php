<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, backup the current boolean values and convert them to status strings
        $leads = DB::table('leads')->select('id', 'air_ticket', 'hotel', 'visa', 'land_package')->get();
        
        // Add new status columns
        Schema::table('leads', function (Blueprint $table) {
            $table->string('air_ticket_status')->default('pending')->after('tour_details');
            $table->string('hotel_status')->default('pending')->after('air_ticket_status');
            $table->string('visa_status')->default('pending')->after('hotel_status');
            $table->string('land_package_status')->default('pending')->after('visa_status');
        });

        // Convert existing boolean values to status strings
        foreach ($leads as $lead) {
            DB::table('leads')
                ->where('id', $lead->id)
                ->update([
                    'air_ticket_status' => $lead->air_ticket ? 'done' : 'pending',
                    'hotel_status' => $lead->hotel ? 'done' : 'pending',
                    'visa_status' => $lead->visa ? 'done' : 'pending',
                    'land_package_status' => $lead->land_package ? 'done' : 'pending',
                ]);
        }

        // Drop the old boolean columns
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['air_ticket', 'hotel', 'visa', 'land_package']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the boolean columns
        Schema::table('leads', function (Blueprint $table) {
            $table->boolean('air_ticket')->default(false)->after('tour_details');
            $table->boolean('hotel')->default(false)->after('air_ticket');
            $table->boolean('visa')->default(false)->after('hotel');
            $table->boolean('land_package')->default(false)->after('visa');
        });

        // Get current status values and convert back to boolean
        $leads = DB::table('leads')->select('id', 'air_ticket_status', 'hotel_status', 'visa_status', 'land_package_status')->get();
        
        foreach ($leads as $lead) {
            DB::table('leads')
                ->where('id', $lead->id)
                ->update([
                    'air_ticket' => $lead->air_ticket_status === 'done',
                    'hotel' => $lead->hotel_status === 'done',
                    'visa' => $lead->visa_status === 'done',
                    'land_package' => $lead->land_package_status === 'done',
                ]);
        }

        // Drop the status columns
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['air_ticket_status', 'hotel_status', 'visa_status', 'land_package_status']);
        });
    }
};
