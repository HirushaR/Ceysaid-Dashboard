<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_payments', function (Blueprint $table): void {
            $table->foreignId('recorded_by')->nullable()->constrained('users')->restrictOnDelete();
        });

        Schema::create('accounting_pilot_state', function (Blueprint $table): void {
            $table->id();
            $table->date('start_date')->nullable();
            $table->date('closed_through')->nullable();
        });

        DB::table('accounting_pilot_state')->insert(['id' => 1]);

        Schema::create('accounting_bookings', function (Blueprint $table): void {
            $table->foreignId('invoice_id')->primary()->constrained()->restrictOnDelete();
            $table->foreignId('lead_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('tour_id')->constrained()->restrictOnDelete();
            $table->date('booking_date');
            $table->date('departure_date');
            $table->date('return_date');
            $table->unsignedBigInteger('contract_minor');
            $table->foreignId('enrolled_by')->constrained('users')->restrictOnDelete();
            $table->text('policy_evidence');
            $table->timestamp('created_at');
        });

        Schema::create('accounting_journals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained('accounting_bookings', 'invoice_id')->restrictOnDelete();
            $table->string('event', 24);
            $table->string('source_key')->nullable()->unique();
            $table->foreignId('customer_payment_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->restrictOnDelete();
            $table->date('posting_date')->index();
            $table->unsignedBigInteger('amount_minor');
            $table->text('evidence');
            $table->json('source_snapshot')->nullable();
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('submitted_at');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
        });

        Schema::create('accounting_journal_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('journal_id')->constrained('accounting_journals')->restrictOnDelete();
            $table->string('account_code', 60);
            $table->unsignedBigInteger('debit_minor')->default(0);
            $table->unsignedBigInteger('credit_minor')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_journal_lines');
        Schema::dropIfExists('accounting_journals');
        Schema::dropIfExists('accounting_bookings');
        Schema::dropIfExists('accounting_pilot_state');
        Schema::table('customer_payments', fn (Blueprint $table) => $table->dropConstrainedForeignId('recorded_by'));
    }
};
