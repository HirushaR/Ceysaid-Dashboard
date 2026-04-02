<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('customer_payments', 'deposit_to')) {
                $table->string('deposit_to', 64)->nullable()->after('payment_method');
            }
        });

        Schema::table('vendor_bills', function (Blueprint $table) {
            if (! Schema::hasColumn('vendor_bills', 'supplier_id')) {
                $table->foreignId('supplier_id')->nullable()->after('invoice_id')->constrained('suppliers')->nullOnDelete();
            }
            if (! Schema::hasColumn('vendor_bills', 'payment_mode')) {
                $table->string('payment_mode', 32)->nullable()->after('payment_date');
            }
            if (! Schema::hasColumn('vendor_bills', 'paid_through')) {
                $table->string('paid_through', 64)->nullable()->after('payment_mode');
            }
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
