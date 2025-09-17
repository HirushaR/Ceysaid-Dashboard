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
        // This migration will run before removing call center fields from leads table
        // We need to create call center calls for existing leads that have call center data
        
        $leads = \App\Models\Lead::whereNotNull('assigned_call_center_user')
            ->where('status', \App\Enums\LeadStatus::CONFIRMED->value)
            ->get();
        
        foreach ($leads as $lead) {
            // Create pre-departure call
            \App\Models\CallCenterCall::create([
                'lead_id' => $lead->id,
                'assigned_call_center_user' => $lead->assigned_call_center_user,
                'call_type' => \App\Models\CallCenterCall::CALL_TYPE_PRE_DEPARTURE,
                'status' => $lead->call_center_status ?? \App\Models\CallCenterCall::STATUS_PENDING,
                'call_notes' => $lead->call_notes,
                'call_attempts' => $lead->call_attempts ?? 0,
                'last_call_attempt' => $lead->last_call_attempt,
                'call_checklist_completed' => $lead->call_checklist_completed,
            ]);
            
            // Create post-arrival call
            \App\Models\CallCenterCall::create([
                'lead_id' => $lead->id,
                'assigned_call_center_user' => $lead->assigned_call_center_user,
                'call_type' => \App\Models\CallCenterCall::CALL_TYPE_POST_ARRIVAL,
                'status' => \App\Models\CallCenterCall::STATUS_PENDING,
                'call_notes' => null,
                'call_attempts' => 0,
                'last_call_attempt' => null,
                'call_checklist_completed' => null,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the migrated call center calls
        \App\Models\CallCenterCall::truncate();
    }
};
