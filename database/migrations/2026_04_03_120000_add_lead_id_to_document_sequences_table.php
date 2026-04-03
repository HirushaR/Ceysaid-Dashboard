<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('document_sequences')) {
            return;
        }

        if (Schema::hasColumn('document_sequences', 'lead_id')) {
            return;
        }

        Schema::table('document_sequences', function (Blueprint $table) {
            $table->unsignedBigInteger('lead_id')->default(0)->after('year');
        });

        Schema::table('document_sequences', function (Blueprint $table) {
            $table->dropUnique(['type', 'year']);
        });

        Schema::table('document_sequences', function (Blueprint $table) {
            $table->unique(['type', 'year', 'lead_id']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('document_sequences') || ! Schema::hasColumn('document_sequences', 'lead_id')) {
            return;
        }

        Schema::table('document_sequences', function (Blueprint $table) {
            $table->dropUnique(['type', 'year', 'lead_id']);
        });

        Schema::table('document_sequences', function (Blueprint $table) {
            $table->unique(['type', 'year']);
        });

        Schema::table('document_sequences', function (Blueprint $table) {
            $table->dropColumn('lead_id');
        });
    }
};
