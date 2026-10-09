<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type')->default('bank');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->date('opening_balance_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        DB::table('financial_accounts')->insert([
            ['code' => 'cash', 'name' => 'Cash', 'type' => 'cash', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'ntb_current', 'name' => 'NTB Current', 'type' => 'bank', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'ntb_saving', 'name' => 'NTB Saving', 'type' => 'bank', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'seylan_saving', 'name' => 'Seylan Saving', 'type' => 'bank', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'seylan_current', 'name' => 'Seylan Current', 'type' => 'bank', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'hnb_saving', 'name' => 'HNB Saving', 'type' => 'bank', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'hnb_current', 'name' => 'HNB Current', 'type' => 'bank', 'created_at' => $now, 'updated_at' => $now],
        ]);

        Schema::create('expense_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->nullable()->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('expenses', function (Blueprint $table): void {
            $table->foreignId('category_id')->nullable()->after('expense_date')->constrained('expense_categories')->nullOnDelete();
        });

        DB::table('expenses')->select('category')->whereNotNull('category')->where('category', '<>', '')->distinct()->orderBy('category')->each(function ($expense) use ($now): void {
            $categoryId = DB::table('expense_categories')->insertGetId([
                'name' => $expense->category,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('expenses')->where('category', $expense->category)->update(['category_id' => $categoryId]);
        });

        Schema::create('internal_transfers', function (Blueprint $table): void {
            $table->id();
            $table->string('transfer_number')->unique();
            $table->date('transfer_date')->index();
            $table->string('from_account')->index();
            $table->string('to_account')->index();
            $table->decimal('amount', 15, 2);
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_transfers');
        Schema::table('expenses', fn (Blueprint $table) => $table->dropConstrainedForeignId('category_id'));
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('financial_accounts');
    }
};
