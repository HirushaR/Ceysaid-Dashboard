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
        Schema::create('vendor_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->string('vendor_name'); // e.g., "IATA", "TRAVEL BUDDY", "MALAYSIA E VISA"
            $table->string('vendor_bill_number'); // e.g., "XO20252345"
            $table->decimal('bill_amount', 15, 2); // Vendor bill amount
            $table->string('service_type'); // e.g., "AIR TICKET", "HOTEL", "VISA"
            $table->text('service_details')->nullable(); // Additional service details
            $table->enum('payment_status', ['pending', 'paid'])->default('pending');
            $table->date('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_bills');
    }
};
