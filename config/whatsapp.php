<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Meta WhatsApp Business Cloud API
    |--------------------------------------------------------------------------
    |
    | Single-account configuration for the WhatsApp number used in ads.
    | Requires a Meta Business account with WhatsApp Business Platform enabled.
    |
    */

    'access_token' => env('WHATSAPP_ACCESS_TOKEN'),

    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),

    'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),

    'app_secret' => env('WHATSAPP_APP_SECRET'),

    'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),

    'webhook_path' => env('WHATSAPP_WEBHOOK_PATH', '/api/webhooks/whatsapp'),

    'media_disk' => env('WHATSAPP_MEDIA_DISK', 'whatsapp-media'),

    /*
    | Allowed outbound attachment MIME types (Meta WhatsApp Cloud API limits apply).
    */
    'allowed_media_mimes' => [
        'image/jpeg',
        'image/png',
        'image/webp',
        'video/mp4',
        'video/3gpp',
        'audio/mpeg',
        'audio/mp4',
        'audio/ogg',
        'audio/aac',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ],

    /*
    | File extensions allowed when the browser reports an ambiguous MIME type
    | (e.g. application/octet-stream for some PDFs).
    */
    'allowed_media_extensions' => [
        'jpg', 'jpeg', 'png', 'webp',
        'mp4', '3gp',
        'mp3', 'ogg', 'aac', 'm4a',
        'pdf', 'doc', 'docx', 'xls', 'xlsx',
    ],

    'max_image_size_kb' => (int) env('WHATSAPP_MAX_IMAGE_SIZE_KB', 5120),

    'max_document_size_kb' => (int) env('WHATSAPP_MAX_DOCUMENT_SIZE_KB', 16384),

    'graph_base_url' => 'https://graph.facebook.com',

    'log_inbound_messages' => env('WHATSAPP_LOG_INBOUND_MESSAGES', true),

];
