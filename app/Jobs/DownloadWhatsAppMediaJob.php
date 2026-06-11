<?php

namespace App\Jobs;

use App\Models\WhatsAppMessage;
use App\Services\WhatsAppApiService;
use App\Support\WhatsAppMediaStorage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class DownloadWhatsAppMediaJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public WhatsAppMessage $message,
    ) {}

    public function handle(WhatsAppApiService $api): void
    {
        $message = $this->message->fresh();

        if (! $message || ! $message->media_id || $message->media_path) {
            return;
        }

        try {
            $mediaInfo = $api->getMediaUrl($message->media_id);
            $downloadUrl = $mediaInfo['url'] ?? null;

            if (! $downloadUrl) {
                return;
            }

            $response = $api->downloadMedia($downloadUrl);
            $path = WhatsAppMediaStorage::storeContents(
                $message->whatsapp_conversation_id,
                $message->wamid,
                $response->body(),
                $message->media_mime_type,
                $message->media_filename,
            );

            $message->update(['media_path' => $path]);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('Failed to download WhatsApp media', [
                'message_id' => $message->id,
                'media_id' => $message->media_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
