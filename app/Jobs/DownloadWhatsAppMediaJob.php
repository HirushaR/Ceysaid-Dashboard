<?php

namespace App\Jobs;

use App\Models\WhatsAppMessage;
use App\Services\WhatsAppApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
            $extension = $this->guessExtension($message->media_mime_type);
            $path = 'conversations/'.$message->whatsapp_conversation_id.'/'.$message->wamid.$extension;

            Storage::disk(config('whatsapp.media_disk'))->put($path, $response->body());

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

    private function guessExtension(?string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg' => '.jpg',
            'image/png' => '.png',
            'image/webp' => '.webp',
            'video/mp4' => '.mp4',
            'audio/ogg', 'audio/ogg; codecs=opus' => '.ogg',
            'application/pdf' => '.pdf',
            default => '.bin',
        };
    }
}
