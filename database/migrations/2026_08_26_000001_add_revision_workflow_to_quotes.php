<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL may reuse the unique lead index for the foreign key. Give the FK a
        // normal index before removing the old one-quote-per-lead constraint.
        Schema::table('quotes', function (Blueprint $table): void {
            $table->index('lead_id', 'quotes_lead_id_index');
        });

        Schema::table('quotes', function (Blueprint $table): void {
            $table->dropUnique(['lead_id']);
            $table->uuid('family_id')->nullable()->after('lead_id');
            $table->unsignedInteger('revision')->default(1)->after('family_id');
            $table->timestamp('sent_at')->nullable()->after('status');
            $table->timestamp('accepted_at')->nullable()->after('sent_at');
            $table->timestamp('rejected_at')->nullable()->after('accepted_at');
            $table->timestamp('expired_at')->nullable()->after('rejected_at');
            $table->foreignId('created_by')->nullable()->after('notes')->constrained('users')->nullOnDelete();
        });

        DB::table('quotes')->orderBy('id')->eachById(function (object $quote): void {
            DB::table('quotes')->where('id', $quote->id)->update(['family_id' => (string) Str::uuid()]);
        });

        Schema::table('quotes', function (Blueprint $table): void {
            $table->unique(['family_id', 'revision']);
            $table->index(['lead_id', 'status']);
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->unique('quote_id');
        });

        Schema::create('finance_action_logs', function (Blueprint $table): void {
            $table->id();
            $table->morphs('subject');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 64);
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->timestamps();
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_action_logs');
        Schema::table('invoices', fn (Blueprint $table) => $table->dropUnique(['quote_id']));
        Schema::table('quotes', function (Blueprint $table): void {
            $table->dropUnique(['family_id', 'revision']);
            $table->dropIndex(['lead_id', 'status']);
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['family_id', 'revision', 'sent_at', 'accepted_at', 'rejected_at', 'expired_at']);
            $table->unique('lead_id');
        });
        Schema::table('quotes', fn (Blueprint $table) => $table->dropIndex('quotes_lead_id_index'));
    }
};
