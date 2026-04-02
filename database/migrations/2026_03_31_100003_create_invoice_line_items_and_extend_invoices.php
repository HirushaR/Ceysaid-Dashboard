<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('quote_id')->nullable()->after('lead_id')->constrained('quotes')->nullOnDelete();
            $table->date('invoice_date')->nullable()->after('invoice_number');
            $table->date('due_date')->nullable()->after('invoice_date');
            $table->string('terms')->nullable()->after('due_date');
            $table->string('subject')->nullable()->after('terms');
        });

        Schema::create('invoice_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_cost_id')->nullable()->constrained('lead_costs')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('description');
            $table->string('customer_details')->nullable();
            $table->decimal('quantity', 15, 4)->default(1);
            $table->decimal('rate', 15, 2)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_line_items');

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('quote_id');
            $table->dropColumn(['invoice_date', 'due_date', 'terms', 'subject']);
        });
    }
};
