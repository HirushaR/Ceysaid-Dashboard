<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->string('bank_account')->default('ntb_current')->after('terms');
            $table->foreignId('sales_person_id')->nullable()->after('lead_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('tours', function (Blueprint $table): void {
            $table->string('tour_type')->default('group_tour')->after('name')->index();
        });

        Schema::table('vendor_bills', function (Blueprint $table): void {
            $table->foreignId('tour_id')->nullable()->after('invoice_id')->constrained('tours')->nullOnDelete();
        });

        Schema::create('expenses', function (Blueprint $table): void {
            $table->id();
            $table->date('expense_date')->index();
            $table->string('category');
            $table->string('description');
            $table->decimal('amount', 15, 2);
            $table->string('payment_mode')->default('bank_transfer');
            $table->string('paid_through')->default('cash');
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');

        Schema::table('vendor_bills', fn (Blueprint $table) => $table->dropConstrainedForeignId('tour_id'));
        Schema::table('tours', function (Blueprint $table): void {
            $table->dropIndex(['tour_type']);
            $table->dropColumn('tour_type');
        });
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('sales_person_id');
            $table->dropColumn('bank_account');
        });
    }
};
