<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            $table->string('deposit_to', 64)->nullable()->after('payment_method');
        });

        Schema::table('vendor_bills', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('invoice_id')->constrained('suppliers')->nullOnDelete();
            $table->string('payment_mode', 32)->nullable()->after('payment_date');
            $table->string('paid_through', 64)->nullable()->after('payment_mode');
        });
    }

    public function down(): void
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            $table->dropColumn('deposit_to');
        });

        Schema::table('vendor_bills', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_id');
            $table->dropColumn(['payment_mode', 'paid_through']);
        });
    }
};
