<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 80)->index();
            $table->nullableMorphs('subject');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::table('leads', function (Blueprint $table): void {
            $table->index(['status', 'created_at'], 'leads_status_created_idx');
            $table->index(['assigned_to', 'status'], 'leads_sales_status_idx');
            $table->index(['assigned_operator', 'status'], 'leads_operator_status_idx');
        });
        Schema::table('invoices', function (Blueprint $table): void {
            $table->index(['invoice_date', 'customer_payment_status'], 'invoices_date_payment_idx');
            $table->index(['due_date', 'balance_amount'], 'invoices_due_balance_idx');
        });
        Schema::table('vendor_bills', fn (Blueprint $table) => $table->index(['supplier_id', 'payment_status'], 'vendor_bills_supplier_payment_idx'));
        Schema::table('customer_payments', fn (Blueprint $table) => $table->index('payment_date', 'customer_payments_date_idx'));
        Schema::table('supplier_payments', fn (Blueprint $table) => $table->index('payment_date', 'supplier_payments_date_idx'));
    }

    public function down(): void
    {
        Schema::table('supplier_payments', fn (Blueprint $table) => $table->dropIndex('supplier_payments_date_idx'));
        Schema::table('customer_payments', fn (Blueprint $table) => $table->dropIndex('customer_payments_date_idx'));
        Schema::table('vendor_bills', fn (Blueprint $table) => $table->dropIndex('vendor_bills_supplier_payment_idx'));
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropIndex('invoices_date_payment_idx');
            $table->dropIndex('invoices_due_balance_idx');
        });
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropIndex('leads_status_created_idx');
            $table->dropIndex('leads_sales_status_idx');
            $table->dropIndex('leads_operator_status_idx');
        });
        Schema::dropIfExists('audit_logs');
    }
};
