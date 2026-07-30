<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payment_number')->unique();
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            $table->string('payment_mode', 32);
            $table->string('paid_through', 64);
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['supplier_id', 'payment_date']);
            $table->index('reference_number');
        });

        Schema::table('vendor_bill_payments', function (Blueprint $table) {
            $table->foreignId('supplier_payment_id')
                ->nullable()
                ->after('vendor_bill_id')
                ->constrained('supplier_payments')
                ->nullOnDelete();
        });

        $now = now();
        DB::table('vendor_bill_payments as payment')
            ->join('vendor_bills as bill', 'bill.id', '=', 'payment.vendor_bill_id')
            ->select([
                'payment.id',
                'payment.amount',
                'payment.payment_date',
                'payment.payment_mode',
                'payment.paid_through',
                'payment.notes',
                'payment.created_at',
                'payment.updated_at',
                'bill.supplier_id',
            ])
            ->orderBy('payment.id')
            ->chunk(100, function ($payments) use ($now): void {
                foreach ($payments as $payment) {
                    $supplierPaymentId = DB::table('supplier_payments')->insertGetId([
                        'supplier_id' => $payment->supplier_id,
                        'payment_number' => 'SP/MIG/'.$payment->id,
                        'payment_date' => $payment->payment_date,
                        'amount' => $payment->amount,
                        'payment_mode' => $payment->payment_mode,
                        'paid_through' => $payment->paid_through,
                        'reference_number' => null,
                        'notes' => $payment->notes,
                        'created_by' => null,
                        'created_at' => $payment->created_at ?? $now,
                        'updated_at' => $payment->updated_at ?? $now,
                    ]);

                    DB::table('vendor_bill_payments')
                        ->where('id', $payment->id)
                        ->update(['supplier_payment_id' => $supplierPaymentId]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('vendor_bill_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_payment_id');
        });

        Schema::dropIfExists('supplier_payments');
    }
};
