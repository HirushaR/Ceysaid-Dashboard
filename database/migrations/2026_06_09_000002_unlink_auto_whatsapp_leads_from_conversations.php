<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('whatsapp_conversations')
            ->join('leads', 'leads.id', '=', 'whatsapp_conversations.lead_id')
            ->where('leads.platform', 'whatsapp')
            ->whereNull('leads.created_by')
            ->whereNull('leads.assigned_to')
            ->update([
                'whatsapp_conversations.lead_id' => null,
            ]);
    }

    public function down(): void
    {
        // Cannot reliably restore unlinked lead associations.
    }
};
