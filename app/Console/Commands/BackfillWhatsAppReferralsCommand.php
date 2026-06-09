<?php

namespace App\Console\Commands;

use App\Services\WhatsAppWebhookHandler;
use Illuminate\Console\Command;

class BackfillWhatsAppReferralsCommand extends Command
{
    protected $signature = 'whatsapp:backfill-referrals';

    protected $description = 'Backfill Meta ad referral data and message timestamps from stored webhook events';

    public function handle(WhatsAppWebhookHandler $handler): int
    {
        $referrals = $handler->backfillReferralsFromWebhookEvents();
        $timestamps = $handler->backfillTimestampsFromWebhookEvents();

        $this->info("Backfilled referral data for {$referrals} message(s).");
        $this->info("Corrected timestamps for {$timestamps} message(s).");

        return self::SUCCESS;
    }
}
