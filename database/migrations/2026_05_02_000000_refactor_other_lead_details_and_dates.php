<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (! Schema::hasColumn('leads', 'other_lead_details')) {
                $table->text('other_lead_details')->nullable()->after('other_lead_status');
            }
            if (! Schema::hasColumn('leads', 'other_lead_start_date')) {
                $table->date('other_lead_start_date')->nullable()->after('other_lead_details');
            }
            if (! Schema::hasColumn('leads', 'other_lead_end_date')) {
                $table->date('other_lead_end_date')->nullable()->after('other_lead_start_date');
            }
        });

        $canMigrateRows = Schema::hasColumn('leads', 'is_other_lead')
            && (Schema::hasColumn('leads', 'ticket_details') || Schema::hasColumn('leads', 'hotel_details'));

        if ($canMigrateRows) {
            DB::table('leads')
                ->where('is_other_lead', true)
                ->orderBy('id')
                ->chunkById(200, function ($rows): void {
                    foreach ($rows as $lead) {
                        $parts = [];
                        if (! empty($lead->ticket_details)) {
                            $parts[] = 'Ticket / flight:'."\n".$lead->ticket_details;
                        }
                        if (! empty($lead->hotel_details)) {
                            $parts[] = 'Hotel / accommodation:'."\n".$lead->hotel_details;
                        }
                        if (! empty($lead->message)) {
                            $parts[] = 'Notes:'."\n".$lead->message;
                        }
                        $merged = trim(implode("\n\n", $parts));
                        $update = ['other_lead_details' => $merged !== '' ? $merged : null];
                        if (Schema::hasColumn('leads', 'message')) {
                            $update['message'] = null;
                        }
                        DB::table('leads')->where('id', $lead->id)->update($update);
                    }
                });
        }

        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'ticket_details')) {
                $table->dropColumn('ticket_details');
            }
            if (Schema::hasColumn('leads', 'hotel_details')) {
                $table->dropColumn('hotel_details');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (! Schema::hasColumn('leads', 'ticket_details')) {
                $table->text('ticket_details')->nullable()->after('other_lead_status');
            }
            if (! Schema::hasColumn('leads', 'hotel_details')) {
                $table->text('hotel_details')->nullable()->after('ticket_details');
            }
        });

        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'other_lead_end_date')) {
                $table->dropColumn('other_lead_end_date');
            }
            if (Schema::hasColumn('leads', 'other_lead_start_date')) {
                $table->dropColumn('other_lead_start_date');
            }
            if (Schema::hasColumn('leads', 'other_lead_details')) {
                $table->dropColumn('other_lead_details');
            }
        });
    }
};
