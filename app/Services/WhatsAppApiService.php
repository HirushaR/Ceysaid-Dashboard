<?php

namespace App\Services;

use App\Exceptions\WhatsAppApiException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppApiService
{
    public function sendTextMessage(string $to, string $body): array
    {
        return $this->sendMessage($to, [
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $body,
            ],
        ]);
    }

    public function uploadMedia(string $contents, string $mimeType, ?string $filename = null): array
    {
        $phoneNumberId = config('whatsapp.phone_number_id');
        $url = $this->graphUrl("{$phoneNumberId}/media");

        $request = Http::withToken(config('whatsapp.access_token'))
            ->attach(
                'file',
                $contents,
                $filename ?: 'media'.self::extensionForMime($mimeType),
                ['Content-Type' => $mimeType],
            );

        $response = $request->post($url, [
            'messaging_product' => 'whatsapp',
            'type' => $mimeType,
        ]);

        return $this->handleResponse($response, 'Failed to upload WhatsApp media');
    }

    public function sendMediaMessage(
        string $to,
        string $type,
        string $mediaId,
        ?string $caption = null,
        ?string $filename = null,
    ): array {
        $mediaPayload = ['id' => $mediaId];

        if ($caption && in_array($type, ['image', 'video', 'document'], true)) {
            $mediaPayload['caption'] = $caption;
        }

        if ($type === 'document' && $filename) {
            $mediaPayload['filename'] = $filename;
        }

        return $this->sendMessage($to, [
            'type' => $type,
            $type => $mediaPayload,
        ]);
    }

    public function getMediaUrl(string $mediaId): array
    {
        $response = Http::withToken(config('whatsapp.access_token'))
            ->get($this->graphUrl($mediaId));

        return $this->handleResponse($response, 'Failed to get WhatsApp media URL');
    }

    public function downloadMedia(string $url): Response
    {
        $response = Http::withToken(config('whatsapp.access_token'))
            ->get($url);

        if (! $response->successful()) {
            throw new WhatsAppApiException(
                'Failed to download WhatsApp media',
                $response->status(),
                $response->json()
            );
        }

        return $response;
    }

    private function sendMessage(string $to, array $payload): array
    {
        $phoneNumberId = config('whatsapp.phone_number_id');
        $url = $this->graphUrl("{$phoneNumberId}/messages");

        $response = Http::withToken(config('whatsapp.access_token'))
            ->post($url, array_merge([
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $this->normalizePhone($to),
            ], $payload));

        return $this->handleResponse($response, 'Failed to send WhatsApp message');
    }

    private function graphUrl(string $path): string
    {
        $version = config('whatsapp.api_version');
        $base = config('whatsapp.graph_base_url');

        return "{$base}/{$version}/{$path}";
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? $phone;
    }

    private function handleResponse(Response $response, string $defaultMessage): array
    {
        if (! $response->successful()) {
            Log::channel('whatsapp')->error($defaultMessage, [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw new WhatsAppApiException(
                $defaultMessage,
                $response->status(),
                $response->json()
            );
        }

        return $response->json();
    }

    public static function extensionForMime(string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg' => '.jpg',
            'image/png' => '.png',
            'image/webp' => '.webp',
            'video/mp4' => '.mp4',
            'application/pdf' => '.pdf',
            default => '.bin',
        };
    }
}
