<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_bills', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Existing standalone bills make a safe non-null rollback impossible.
    }
};
