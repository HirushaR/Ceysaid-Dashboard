<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->json('referral')->nullable()->after('body');
        });

        Schema::table('whatsapp_conversations', function (Blueprint $table) {
            $table->string('referral_source_id')->nullable()->after('lead_id');
            $table->string('referral_source_type')->nullable()->after('referral_source_id');
            $table->string('referral_source_url')->nullable()->after('referral_source_type');
            $table->string('referral_headline')->nullable()->after('referral_source_url');
            $table->string('referral_ctwa_clid')->nullable()->after('referral_headline');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->string('meta_ad_id')->nullable()->after('platform');
            $table->string('meta_ctwa_clid')->nullable()->after('meta_ad_id');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropColumn('referral');
        });

        Schema::table('whatsapp_conversations', function (Blueprint $table) {
            $table->dropColumn([
                'referral_source_id',
                'referral_source_type',
                'referral_source_url',
                'referral_headline',
                'referral_ctwa_clid',
            ]);
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['meta_ad_id', 'meta_ctwa_clid']);
        });
    }
};
