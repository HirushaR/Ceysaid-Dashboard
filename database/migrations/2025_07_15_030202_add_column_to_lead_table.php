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
        Schema::table('leads', function (Blueprint $table) {
            //  
            $table->string('customer_name')->nullable();
            $table->text('tour')->nullable();
            $table->text('message')->nullable();
            $table->string('contact_method')->nullable();
            $table->string('contact_value')->nullable();
            $table->string('subject')->nullable();
            $table->string('country')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'customer_name',
                'tour',
                'message',
                'contact_method',
                'contact_value',
                'subject',
                'country'
            ]);
        });
    }
};
