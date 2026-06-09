<?php

namespace App\Jobs;

use App\Models\WebhookEvent;
use App\Services\WhatsAppWebhookHandler;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessWhatsAppWebhookJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public int $webhookEventId,
    ) {}

    public function handle(WhatsAppWebhookHandler $handler): void
    {
        $event = WebhookEvent::find($this->webhookEventId);

        if (! $event || $event->processed_at) {
            return;
        }

        try {
            $handler->handle($event->payload);
            $event->update([
                'processed_at' => now(),
                'error_message' => null,
            ]);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('Webhook processing failed', [
                'webhook_event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);

            $event->update([
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
