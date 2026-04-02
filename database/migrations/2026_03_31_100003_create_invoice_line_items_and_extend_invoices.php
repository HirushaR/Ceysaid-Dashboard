<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'quote_id')) {
                $table->foreignId('quote_id')->nullable()->after('lead_id')->constrained('quotes')->nullOnDelete();
            }
            if (! Schema::hasColumn('invoices', 'invoice_date')) {
                $table->date('invoice_date')->nullable()->after('invoice_number');
            }
            if (! Schema::hasColumn('invoices', 'due_date')) {
                $table->date('due_date')->nullable()->after('invoice_date');
            }
            if (! Schema::hasColumn('invoices', 'terms')) {
                $table->string('terms')->nullable()->after('due_date');
            }
            if (! Schema::hasColumn('invoices', 'subject')) {
                $table->string('subject')->nullable()->after('terms');
            }
        });

        if (Schema::hasTable('invoice_line_items')) {
            return;
        }

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
