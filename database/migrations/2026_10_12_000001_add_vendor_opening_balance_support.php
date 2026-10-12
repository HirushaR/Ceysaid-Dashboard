<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_bills', function (Blueprint $table): void {
            $table->boolean('is_opening_balance')->default(false)->after('vendor_bill_number')->index();
        });
    }

    public function down(): void
    {
        Schema::table('vendor_bills', function (Blueprint $table): void {
            $table->dropIndex(['is_opening_balance']);
            $table->dropColumn('is_opening_balance');
        });
    }
};
