<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vendor_bill_line_items')) {
            return;
        }

        Schema::create('vendor_bill_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_bill_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('description');
            $table->decimal('quantity', 15, 4)->default(1);
            $table->decimal('rate', 15, 2)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();
        });

        if (! Schema::hasTable('vendor_bills')) {
            return;
        }

        $now = now();

        DB::table('vendor_bills')->orderBy('id')->chunk(100, function ($rows) use ($now): void {
            foreach ($rows as $vb) {
                $vb = (array) $vb;
                $id = (int) $vb['id'];
                $exists = DB::table('vendor_bill_line_items')->where('vendor_bill_id', $id)->exists();
                if ($exists) {
                    continue;
                }

                $type = (string) ($vb['service_type'] ?? 'Service');
                $details = trim((string) ($vb['service_details'] ?? ''));
                $desc = $details !== '' ? $type."\n".$details : $type;
                $amount = (float) ($vb['bill_amount'] ?? 0);

                DB::table('vendor_bill_line_items')->insert([
                    'vendor_bill_id' => $id,
                    'sort_order' => 0,
                    'description' => $desc !== '' ? $desc : 'Service',
                    'quantity' => 1,
                    'rate' => $amount,
                    'amount' => $amount,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_bill_line_items');
    }
};
