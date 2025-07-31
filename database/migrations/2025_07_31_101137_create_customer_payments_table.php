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
        Schema::create('customer_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2); // Payment amount
            $table->date('payment_date'); // When payment was received
            $table->string('receipt_number')->nullable(); // Receipt number for this payment
            $table->string('payment_method')->nullable(); // How they paid (bank transfer, cash, etc.)
            $table->text('notes')->nullable(); // Any notes about this payment
            $table->timestamps();

            // Indexes for better performance
            $table->index(['invoice_id', 'payment_date']);
            $table->index('receipt_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_payments');
    }
};