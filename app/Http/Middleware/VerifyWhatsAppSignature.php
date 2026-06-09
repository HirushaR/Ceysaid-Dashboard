<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyWhatsAppSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $appSecret = config('whatsapp.app_secret');

        if (empty($appSecret)) {
            Log::channel('whatsapp')->warning('WhatsApp app secret not configured; skipping signature verification');

            return $next($request);
        }

        $signature = $request->header('X-Hub-Signature-256');

        if (! $signature || ! str_starts_with($signature, 'sha256=')) {
            abort(403, 'Invalid signature header');
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret);

        if (! hash_equals($expected, $signature)) {
            abort(403, 'Invalid signature');
        }

        return $next($request);
    }
}
