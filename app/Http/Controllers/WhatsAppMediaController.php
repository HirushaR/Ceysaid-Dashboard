<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class WhatsAppMediaController extends Controller
{
    public function show(Request $request, WhatsAppMessage $message): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin()) {
            abort(403);
        }

        if (! $message->media_path) {
            abort(404);
        }

        $disk = Storage::disk(config('whatsapp.media_disk'));

        if (! $disk->exists($message->media_path)) {
            abort(404);
        }

        return response($disk->get($message->media_path), 200, [
            'Content-Type' => $message->media_mime_type ?? 'application/octet-stream',
            'Content-Disposition' => 'inline',
        ]);
    }
}
