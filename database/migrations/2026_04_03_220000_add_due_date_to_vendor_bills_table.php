<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_bills', function (Blueprint $table) {
            if (! Schema::hasColumn('vendor_bills', 'due_date')) {
                $table->date('due_date')->nullable()->after('bill_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vendor_bills', function (Blueprint $table) {
            if (Schema::hasColumn('vendor_bills', 'due_date')) {
                $table->dropColumn('due_date');
            }
        });
    }
};
