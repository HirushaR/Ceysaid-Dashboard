<?php

namespace App\Console\Commands;

use App\Models\WhatsAppConversation;
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

        $activity = 0;

        WhatsAppConversation::query()
            ->with(['messages' => fn ($query) => $query->orderByRaw('COALESCE(sent_at, created_at) DESC')->orderByDesc('id')->limit(1)])
            ->chunkById(100, function ($conversations) use (&$activity): void {
                foreach ($conversations as $conversation) {
                    $conversation->syncFromLatestMessage();
                    $activity++;
                }
            });

        $this->info("Backfilled referral data for {$referrals} message(s).");
        $this->info("Corrected timestamps for {$timestamps} message(s).");
        $this->info("Synced last activity for {$activity} conversation(s).");

        return self::SUCCESS;
    }
}
