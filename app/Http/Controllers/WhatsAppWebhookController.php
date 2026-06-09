<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessWhatsAppWebhookJob;
use App\Services\WhatsAppWebhookHandler;
use App\Support\WhatsAppLogContext;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request): Response|string
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === config('whatsapp.verify_token')) {
            Log::channel('whatsapp')->info('Webhook verified successfully');

            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::channel('whatsapp')->warning('Webhook verification failed', [
            'mode' => $mode,
        ]);

        abort(403, 'Verification failed');
    }

    public function receive(Request $request): Response
    {
        $payload = $request->all();

        if (config('whatsapp.log_inbound_messages', true)) {
            Log::channel('whatsapp')->info('Webhook payload received', WhatsAppLogContext::flatten([
                'payload' => $payload,
            ]));
        }

        $event = WhatsAppWebhookHandler::recordWebhookEvent($payload);

        if ($event) {
            ProcessWhatsAppWebhookJob::dispatch($event->id);
        }

        return response('EVENT_RECEIVED', 200);
    }
}
