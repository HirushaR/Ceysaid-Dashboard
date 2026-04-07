<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_bill_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_bill_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->string('payment_mode', 32);
            $table->string('paid_through', 32);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE vendor_bills MODIFY COLUMN payment_status ENUM('pending','partial','paid') NOT NULL DEFAULT 'pending'");
        }

        $now = now();
        $paidBills = DB::table('vendor_bills')->where('payment_status', 'paid')->get();
        foreach ($paidBills as $bill) {
            DB::table('vendor_bill_payments')->insert([
                'vendor_bill_id' => $bill->id,
                'amount' => $bill->bill_amount,
                'payment_date' => $bill->payment_date ?? $now->toDateString(),
                'payment_mode' => $bill->payment_mode ?? 'bank_transfer',
                'paid_through' => $bill->paid_through ?? 'cash',
                'notes' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_bill_payments');

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE vendor_bills MODIFY COLUMN payment_status ENUM('pending','paid') NOT NULL DEFAULT 'pending'");
        }
    }
};
