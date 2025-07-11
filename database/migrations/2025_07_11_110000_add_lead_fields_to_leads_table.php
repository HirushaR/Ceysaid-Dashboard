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
       
          
            $table->integer('number_of_adults')->nullable();
            $table->integer('number_of_children')->nullable();
            $table->integer('number_of_infants')->nullable();
            $table->string('priority')->nullable();
            $table->date('arrival_date')->nullable();
            $table->date('depature_date')->nullable();
            $table->integer('number_of_days')->nullable();
            // Foreign keys (optional, uncomment if you want constraints)
            // $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            // $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            // $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
            // $table->foreign('assigned_operator')->references('id')->on('users')->nullOnDelete();
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
                'customer_id',
                'tour',
                'message',
                'contact_method',
                'contact_value',
                'subject',
                'country',
                'destination',
                'number_of_adults',
                'number_of_children',
                'number_of_infants',
                'priority',
                'arrival_date',
                'depature_date',
                'number_of_days',
                'additional_details',
            ]);
        });
    }
}; 