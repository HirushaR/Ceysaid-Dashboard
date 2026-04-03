<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('quotes')) {
            return;
        }

        $duplicateLeadIds = DB::table('quotes')
            ->select('lead_id')
            ->groupBy('lead_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('lead_id');

        foreach ($duplicateLeadIds as $leadId) {
            $keepId = (int) DB::table('quotes')
                ->where('lead_id', $leadId)
                ->orderByDesc('id')
                ->value('id');

            $deleteIds = DB::table('quotes')
                ->where('lead_id', $leadId)
                ->where('id', '!=', $keepId)
                ->pluck('id');

            foreach ($deleteIds as $deleteId) {
                DB::table('invoices')->where('quote_id', $deleteId)->update(['quote_id' => null]);
            }

            DB::table('quotes')->whereIn('id', $deleteIds)->delete();
        }

        if (Schema::hasIndex('quotes', 'quotes_lead_id_unique', 'unique')) {
            return;
        }

        Schema::table('quotes', function (Blueprint $table) {
            $table->unique('lead_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('quotes')) {
            return;
        }

        if (! Schema::hasIndex('quotes', 'quotes_lead_id_unique', 'unique')) {
            return;
        }

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropUnique(['lead_id']);
        });
    }
};
