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

    'graph_base_url' => 'https://graph.facebook.com',

    'log_inbound_messages' => env('WHATSAPP_LOG_INBOUND_MESSAGES', true),

];
