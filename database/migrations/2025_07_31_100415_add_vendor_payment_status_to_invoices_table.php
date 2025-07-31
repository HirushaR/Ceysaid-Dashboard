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
        Schema::table('invoices', function (Blueprint $table) {
            // Rename current status to customer_payment_status for clarity
            $table->renameColumn('status', 'customer_payment_status');
            
            // Add vendor payment status tracking
            $table->enum('vendor_payment_status', ['pending', 'partial', 'paid'])->default('pending')->after('customer_payment_status');
            
            // Add a field to track customer payment balance (for partial payments)
            $table->decimal('balance_amount', 15, 2)->nullable()->after('payment_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Revert column name
            $table->renameColumn('customer_payment_status', 'status');
            
            // Drop new columns
            $table->dropColumn(['vendor_payment_status', 'balance_amount']);
        });
    }
};