<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WhatsAppMediaStorage
{
    public static function disk(): Filesystem
    {
        return Storage::disk(config('whatsapp.media_disk'));
    }

    public static function storeContents(
        int $conversationId,
        string $identifier,
        string $contents,
        ?string $mimeType,
        ?string $originalFilename = null,
    ): string {
        $extension = self::extensionForMime($mimeType, $originalFilename);
        $path = self::buildPath($conversationId, $identifier, $extension);

        self::disk()->put($path, $contents, self::visibilityOptions());

        return $path;
    }

    public static function storeUploadedFile(int $conversationId, UploadedFile $file): array
    {
        $mimeType = $file->getMimeType() ?: 'application/octet-stream';
        $type = self::messageTypeForMime($mimeType);
        $identifier = 'outbound-'.Str::uuid();
        $path = self::storeContents(
            $conversationId,
            $identifier,
            $file->get(),
            $mimeType,
            $file->getClientOriginalName(),
        );

        return [
            'path' => $path,
            'mime_type' => $mimeType,
            'filename' => $file->getClientOriginalName(),
            'type' => $type,
        ];
    }

    public static function exists(?string $path): bool
    {
        return filled($path) && self::disk()->exists($path);
    }

    public static function get(?string $path): ?string
    {
        if (! self::exists($path)) {
            return null;
        }

        return self::disk()->get($path);
    }

    public static function messageTypeForMime(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        if (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }

        return 'document';
    }

    public static function extensionForMime(?string $mimeType, ?string $filename = null): string
    {
        if ($filename && ($ext = pathinfo($filename, PATHINFO_EXTENSION))) {
            return '.'.strtolower($ext);
        }

        return match ($mimeType) {
            'image/jpeg' => '.jpg',
            'image/png' => '.png',
            'image/webp' => '.webp',
            'image/gif' => '.gif',
            'video/mp4' => '.mp4',
            'video/3gpp' => '.3gp',
            'audio/ogg', 'audio/ogg; codecs=opus' => '.ogg',
            'audio/mpeg' => '.mp3',
            'audio/aac' => '.aac',
            'application/pdf' => '.pdf',
            'application/msword' => '.doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => '.docx',
            'application/vnd.ms-excel' => '.xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => '.xlsx',
            default => '.bin',
        };
    }

    private static function buildPath(int $conversationId, string $identifier, string $extension): string
    {
        $safeIdentifier = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $identifier) ?: Str::uuid()->toString();

        return "conversations/{$conversationId}/{$safeIdentifier}{$extension}";
    }

    /**
     * @return array<string, mixed>
     */
    private static function visibilityOptions(): array
    {
        $disk = config('whatsapp.media_disk');

        if ($disk === 'whatsapp-media' && config('filesystems.disks.whatsapp-media.driver') === 's3') {
            return ['visibility' => 'private'];
        }

        return [];
    }
}
