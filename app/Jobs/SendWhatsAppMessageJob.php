<?php

namespace App\Jobs;

use App\Models\WhatsAppMessage;
use App\Services\WhatsAppApiService;
use App\Support\WhatsAppMediaStorage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendWhatsAppMessageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public int $messageId,
    ) {}

    public function handle(WhatsAppApiService $api): void
    {
        $message = WhatsAppMessage::with('conversation.contact')->find($this->messageId);

        if (! $message || $message->direction !== 'outbound') {
            return;
        }

        if ($message->status !== 'pending') {
            return;
        }

        $phone = $message->conversation?->contact?->phone;
        if (! $phone) {
            $message->update(['status' => 'failed']);

            return;
        }

        try {
            if ($message->type === 'text') {
                if (! $message->body) {
                    $message->update(['status' => 'failed']);

                    return;
                }

                $response = $api->sendTextMessage($phone, $message->body);
            } elseif ($message->media_path) {
                $contents = WhatsAppMediaStorage::get($message->media_path);

                if (! $contents) {
                    $message->update(['status' => 'failed']);

                    return;
                }

                $upload = $api->uploadMedia(
                    $contents,
                    $message->media_mime_type ?: 'application/octet-stream',
                    $message->media_filename,
                );

                $mediaId = $upload['id'] ?? null;

                if (! $mediaId) {
                    $message->update(['status' => 'failed']);

                    return;
                }

                $response = $api->sendMediaMessage(
                    $phone,
                    $message->type,
                    $mediaId,
                    $message->body,
                    $message->media_filename,
                );
            } else {
                $message->update(['status' => 'failed']);

                return;
            }

            $wamid = $response['messages'][0]['id'] ?? null;

            $message->update([
                'wamid' => $wamid ?: $message->wamid,
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('Failed to send outbound WhatsApp message', [
                'message_id' => $message->id,
                'type' => $message->type,
                'error' => $e->getMessage(),
            ]);

            $message->update(['status' => 'failed']);

            throw $e;
        }
    }
}
