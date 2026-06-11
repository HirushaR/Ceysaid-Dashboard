<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Support\WhatsAppMediaStorage;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WhatsAppMediaController extends Controller
{
    public function show(Request $request, WhatsAppMessage $message): Response
    {
        $user = $request->user();

        if (! $user || ! $this->canViewMedia($user, $message)) {
            abort(403);
        }

        if (! $message->media_path || ! WhatsAppMediaStorage::exists($message->media_path)) {
            abort(404);
        }

        $filename = $message->media_filename ?: basename($message->media_path);
        $mimeType = $message->media_mime_type ?: 'application/octet-stream';
        $disposition = $message->isImage() || $message->isVideo() || $message->isAudio()
            ? 'inline'
            : 'attachment';

        return WhatsAppMediaStorage::disk()->response($message->media_path, $filename, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => "{$disposition}; filename=\"{$filename}\"",
        ]);
    }

    private function canViewMedia(User $user, WhatsAppMessage $message): bool
    {
        $conversation = $message->conversation;

        if (! $conversation) {
            return false;
        }

        if ($user->isAdmin()) {
            return $conversation->isAssigned();
        }

        if ($user->isSales()) {
            return $conversation->isAssignedTo($user);
        }

        return false;
    }
}
