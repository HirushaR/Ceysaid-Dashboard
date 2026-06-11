<?php

namespace App\Services;

use App\Jobs\DownloadWhatsAppMediaJob;
use App\Models\Lead;
use App\Models\WebhookEvent;
use App\Models\WhatsAppContact;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppMessageStatus;
use App\Support\WhatsAppLogContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsAppWebhookHandler
{
    public function handle(array $payload): void
    {
        if (($payload['object'] ?? null) !== 'whatsapp_business_account') {
            Log::channel('whatsapp')->info('Ignoring non-WhatsApp webhook object', [
                'object' => $payload['object'] ?? null,
            ]);

            return;
        }

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];
                $field = $change['field'] ?? 'unknown';

                if (isset($value['messages'])) {
                    foreach ($value['messages'] as $message) {
                        $this->processInboundMessage($value, $message);
                    }
                }

                if (isset($value['statuses'])) {
                    foreach ($value['statuses'] as $status) {
                        $this->processStatusUpdate($status);
                    }
                }

                if (! isset($value['messages']) && ! isset($value['statuses'])) {
                    Log::channel('whatsapp')->debug('Unhandled webhook change', [
                        'field' => $field,
                        'keys' => array_keys($value),
                    ]);
                }
            }
        }
    }

    private function processInboundMessage(array $value, array $message): void
    {
        $wamid = $message['id'] ?? null;
        if (! $wamid) {
            return;
        }

        $referral = $this->parseReferral($message);
        $existingMessage = WhatsAppMessage::where('wamid', $wamid)->first();

        if ($existingMessage) {
            if ($referral && ! $existingMessage->referral) {
                $this->backfillReferral($existingMessage, $referral);
            }

            $this->logInboundMessageEvent('duplicate_skipped', $value, $message, [
                'existing_whatsapp_message' => $existingMessage->fresh()?->toArray(),
                'existing_conversation' => $existingMessage->conversation?->fresh()?->toArray(),
                'existing_contact' => $existingMessage->conversation?->contact?->fresh()?->toArray(),
                'existing_lead' => $existingMessage->conversation?->lead?->fresh()?->toArray(),
                'parsed_referral' => $referral,
            ]);

            return;
        }

        $from = $message['from'] ?? null;
        if (! $from) {
            return;
        }

        $contactData = $this->findContactProfile($value, $from);
        $parsed = $this->parseMessageContent($message);

        $whatsappMessage = null;

        DB::transaction(function () use ($from, $contactData, $message, $wamid, $parsed, $referral, &$whatsappMessage) {
            $contact = WhatsAppContact::updateOrCreate(
                ['wa_id' => $from],
                [
                    'phone' => $from,
                    'profile_name' => $contactData['profile_name'] ?? null,
                ]
            );

            $conversation = WhatsAppConversation::firstOrCreate(
                ['whatsapp_contact_id' => $contact->id],
                ['unread_count' => 0]
            );

            if ($referral) {
                $this->applyReferralToConversation($conversation, $referral);

                if ($conversation->lead) {
                    $this->applyReferralToLead($conversation->lead, $referral);
                }
            }

            $sentAt = $this->parseMessageTimestamp($message);

            $whatsappMessage = WhatsAppMessage::create([
                'whatsapp_conversation_id' => $conversation->id,
                'wamid' => $wamid,
                'direction' => 'inbound',
                'type' => $parsed['type'],
                'body' => $parsed['body'],
                'referral' => $referral,
                'media_id' => $parsed['media_id'],
                'media_mime_type' => $parsed['media_mime_type'],
                'media_filename' => $parsed['media_filename'] ?? null,
                'status' => 'received',
                'sent_at' => $sentAt,
            ]);

            if ($parsed['media_id']) {
                DownloadWhatsAppMediaJob::dispatch($whatsappMessage);
            }

            $conversation->incrementUnread();
            $conversation->syncFromLatestMessage();
        });

        if ($whatsappMessage) {
            $contact = $whatsappMessage->conversation?->contact;
            $conversation = $whatsappMessage->conversation?->fresh();
            $lead = $conversation?->lead?->fresh();

            $this->logInboundMessageEvent('stored', $value, $message, [
                'parsed_content' => $parsed,
                'parsed_referral' => $referral,
                'whatsapp_contact' => $contact?->fresh()?->toArray(),
                'whatsapp_conversation' => $conversation?->toArray(),
                'whatsapp_message' => $whatsappMessage->fresh()?->toArray(),
                'lead' => $lead?->toArray(),
            ]);
        }
    }

    private function processStatusUpdate(array $status): void
    {
        $wamid = $status['id'] ?? null;
        if (! $wamid) {
            return;
        }

        $message = WhatsAppMessage::where('wamid', $wamid)->first();
        if (! $message) {
            return;
        }

        $statusValue = $status['status'] ?? 'unknown';
        $statusAt = $this->parseMessageTimestamp($status);

        WhatsAppMessageStatus::create([
            'whatsapp_message_id' => $message->id,
            'status' => $statusValue,
            'status_at' => $statusAt,
            'raw_payload' => $status,
        ]);

        $message->update(['status' => $statusValue]);
    }

    private function findContactProfile(array $value, string $waId): array
    {
        foreach ($value['contacts'] ?? [] as $contact) {
            if (($contact['wa_id'] ?? null) === $waId) {
                return [
                    'profile_name' => $contact['profile']['name'] ?? null,
                ];
            }
        }

        return [];
    }

    private function parseMessageContent(array $message): array
    {
        $type = $message['type'] ?? 'unknown';

        return match ($type) {
            'text' => [
                'type' => 'text',
                'body' => $message['text']['body'] ?? null,
                'preview' => $message['text']['body'] ?? '[Text message]',
                'media_id' => null,
                'media_mime_type' => null,
                'media_filename' => null,
            ],
            'image' => [
                'type' => 'image',
                'body' => $message['image']['caption'] ?? null,
                'preview' => $message['image']['caption'] ?? '[Image]',
                'media_id' => $message['image']['id'] ?? null,
                'media_mime_type' => $message['image']['mime_type'] ?? null,
                'media_filename' => null,
            ],
            'document' => [
                'type' => 'document',
                'body' => $message['document']['caption'] ?? ($message['document']['filename'] ?? null),
                'preview' => $message['document']['filename'] ?? '[Document]',
                'media_id' => $message['document']['id'] ?? null,
                'media_mime_type' => $message['document']['mime_type'] ?? null,
                'media_filename' => $message['document']['filename'] ?? null,
            ],
            'audio' => [
                'type' => 'audio',
                'body' => null,
                'preview' => '[Audio]',
                'media_id' => $message['audio']['id'] ?? null,
                'media_mime_type' => $message['audio']['mime_type'] ?? null,
                'media_filename' => null,
            ],
            'video' => [
                'type' => 'video',
                'body' => $message['video']['caption'] ?? null,
                'preview' => $message['video']['caption'] ?? '[Video]',
                'media_id' => $message['video']['id'] ?? null,
                'media_mime_type' => $message['video']['mime_type'] ?? null,
                'media_filename' => null,
            ],
            'sticker' => [
                'type' => 'sticker',
                'body' => null,
                'preview' => '[Sticker]',
                'media_id' => $message['sticker']['id'] ?? null,
                'media_mime_type' => $message['sticker']['mime_type'] ?? null,
                'media_filename' => null,
            ],
            'location' => [
                'type' => 'location',
                'body' => $this->formatLocation($message['location'] ?? []),
                'preview' => '[Location]',
                'media_id' => null,
                'media_mime_type' => null,
                'media_filename' => null,
            ],
            default => [
                'type' => $type,
                'body' => null,
                'preview' => '['.ucfirst($type).' message]',
                'media_id' => null,
                'media_mime_type' => null,
                'media_filename' => null,
            ],
        };
    }

    private function logInboundMessageEvent(string $event, array $value, array $message, array $extra = []): void
    {
        if (! config('whatsapp.log_inbound_messages', true)) {
            return;
        }

        Log::channel('whatsapp')->info("Inbound WhatsApp message: {$event}", WhatsAppLogContext::flatten(array_merge([
            'event' => $event,
            'raw_webhook_message' => $message,
            'raw_webhook_metadata' => $value['metadata'] ?? null,
            'raw_webhook_contacts' => $value['contacts'] ?? null,
        ], $extra)));
    }

    /**
     * Meta webhook timestamps are Unix seconds in UTC.
     */
    private function parseMessageTimestamp(array $payload): Carbon
    {
        if (! isset($payload['timestamp'])) {
            return now();
        }

        return Carbon::createFromTimestampUTC((int) $payload['timestamp'])
            ->timezone(config('app.timezone'));
    }

    public function backfillTimestampsFromWebhookEvents(): int
    {
        $updated = 0;

        WebhookEvent::query()
            ->whereNotNull('processed_at')
            ->orderBy('id')
            ->chunkById(100, function ($events) use (&$updated) {
                foreach ($events as $event) {
                    foreach ($event->payload['entry'] ?? [] as $entry) {
                        foreach ($entry['changes'] ?? [] as $change) {
                            foreach ($change['value']['messages'] ?? [] as $message) {
                                $wamid = $message['id'] ?? null;
                                if (! $wamid) {
                                    continue;
                                }

                                $existingMessage = WhatsAppMessage::where('wamid', $wamid)->first();
                                if (! $existingMessage) {
                                    continue;
                                }

                                $sentAt = $this->parseMessageTimestamp($message);

                                if ($existingMessage->sent_at?->eq($sentAt)) {
                                    continue;
                                }

                                $existingMessage->update(['sent_at' => $sentAt]);

                                $conversation = $existingMessage->conversation;
                                if ($conversation) {
                                    $latest = $conversation->messages()
                                        ->orderByDesc('sent_at')
                                        ->orderByDesc('id')
                                        ->first();

                                    if ($latest) {
                                        $conversation->update([
                                            'last_message_at' => $latest->sent_at ?? $latest->created_at,
                                        ]);
                                    }
                                }

                                $updated++;
                            }
                        }
                    }
                }
            });

        return $updated;
    }

    /**
     * Meta includes referral on the first message when a user clicks a Click-to-WhatsApp ad.
     *
     * @see https://developers.facebook.com/docs/whatsapp/cloud-api/webhooks/payload-examples#click-to-whatsapp-ads
     */
    private function parseReferral(array $message): ?array
    {
        $referral = $message['referral'] ?? null;

        if (! is_array($referral) || empty($referral['source_id'])) {
            return null;
        }

        return array_filter([
            'source_id' => $referral['source_id'] ?? null,
            'source_type' => $referral['source_type'] ?? null,
            'source_url' => $referral['source_url'] ?? null,
            'headline' => $referral['headline'] ?? null,
            'body' => $referral['body'] ?? null,
            'media_type' => $referral['media_type'] ?? null,
            'image_url' => $referral['image_url'] ?? null,
            'video_url' => $referral['video_url'] ?? null,
            'thumbnail_url' => $referral['thumbnail_url'] ?? null,
            'ctwa_clid' => $referral['ctwa_clid'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    public function backfillReferralsFromWebhookEvents(): int
    {
        $updated = 0;

        WebhookEvent::query()
            ->whereNotNull('processed_at')
            ->orderBy('id')
            ->chunkById(100, function ($events) use (&$updated) {
                foreach ($events as $event) {
                    foreach ($event->payload['entry'] ?? [] as $entry) {
                        foreach ($entry['changes'] ?? [] as $change) {
                            foreach ($change['value']['messages'] ?? [] as $message) {
                                $referral = $this->parseReferral($message);
                                if (! $referral) {
                                    continue;
                                }

                                $wamid = $message['id'] ?? null;
                                if (! $wamid) {
                                    continue;
                                }

                                $existingMessage = WhatsAppMessage::where('wamid', $wamid)->first();
                                if ($existingMessage && ! $existingMessage->referral) {
                                    $this->backfillReferral($existingMessage, $referral);
                                    $updated++;
                                }
                            }
                        }
                    }
                }
            });

        return $updated;
    }

    private function backfillReferral(WhatsAppMessage $message, array $referral): void
    {
        DB::transaction(function () use ($message, $referral) {
            $message->update(['referral' => $referral]);

            $conversation = $message->conversation;
            if (! $conversation) {
                return;
            }

            $this->applyReferralToConversation($conversation, $referral);

            if ($conversation->lead) {
                $this->applyReferralToLead($conversation->lead, $referral);
            }
        });

        Log::channel('whatsapp')->info('Backfilled Click-to-WhatsApp referral', [
            'message_id' => $message->id,
            'source_id' => $referral['source_id'],
        ]);
    }

    private function applyReferralToConversation(WhatsAppConversation $conversation, array $referral): void
    {
        if ($conversation->referral_source_id) {
            return;
        }

        $conversation->update([
            'referral_source_id' => $referral['source_id'] ?? null,
            'referral_source_type' => $referral['source_type'] ?? null,
            'referral_source_url' => $referral['source_url'] ?? null,
            'referral_headline' => $referral['headline'] ?? null,
            'referral_ctwa_clid' => $referral['ctwa_clid'] ?? null,
        ]);
    }

    private function applyReferralToLead(Lead $lead, array $referral): void
    {
        $updates = [];

        if (! $lead->meta_ad_id && ! empty($referral['source_id'])) {
            $updates['meta_ad_id'] = $referral['source_id'];
        }

        if (! $lead->meta_ctwa_clid && ! empty($referral['ctwa_clid'])) {
            $updates['meta_ctwa_clid'] = $referral['ctwa_clid'];
        }

        if ($updates !== []) {
            $lead->update($updates);
        }
    }

    private function leadMessagePreview(string $preview, ?array $referral): string
    {
        if (! $referral || ($referral['source_type'] ?? null) !== 'ad') {
            return $preview;
        }

        $adId = $referral['source_id'] ?? 'unknown';

        return "[Meta Ad: {$adId}] {$preview}";
    }

    private function formatLocation(array $location): ?string
    {
        if (empty($location)) {
            return null;
        }

        $name = $location['name'] ?? null;
        $address = $location['address'] ?? null;
        $lat = $location['latitude'] ?? null;
        $lng = $location['longitude'] ?? null;

        $parts = array_filter([$name, $address, $lat !== null ? "{$lat}, {$lng}" : null]);

        return $parts ? implode(' — ', $parts) : null;
    }

    public static function buildIdempotencyKey(array $payload): string
    {
        $messages = [];
        $statuses = [];

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];
                foreach ($value['messages'] ?? [] as $message) {
                    if ($id = ($message['id'] ?? null)) {
                        $messages[] = $id;
                    }
                }
                foreach ($value['statuses'] ?? [] as $status) {
                    $statusId = ($status['id'] ?? 'unknown').':'.($status['status'] ?? 'unknown').':'.($status['timestamp'] ?? '');
                    $statuses[] = $statusId;
                }
            }
        }

        if ($messages !== []) {
            sort($messages);

            return 'msg:'.implode(',', $messages);
        }

        if ($statuses !== []) {
            sort($statuses);

            return 'status:'.implode(',', $statuses);
        }

        return 'raw:'.hash('sha256', json_encode($payload));
    }

    public static function recordWebhookEvent(array $payload): ?WebhookEvent
    {
        $key = self::buildIdempotencyKey($payload);

        $existing = WebhookEvent::where('idempotency_key', $key)->first();

        if ($existing) {
            return $existing->processed_at ? null : $existing;
        }

        return WebhookEvent::create([
            'idempotency_key' => $key,
            'event_type' => self::detectEventType($payload),
            'payload' => $payload,
        ]);
    }

    private static function detectEventType(array $payload): string
    {
        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];
                if (isset($value['messages'])) {
                    return 'messages';
                }
                if (isset($value['statuses'])) {
                    return 'statuses';
                }
            }
        }

        return 'unknown';
    }
}
