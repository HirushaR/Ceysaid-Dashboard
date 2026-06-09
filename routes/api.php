<?php

use App\Http\Controllers\WhatsAppWebhookController;
use App\Http\Middleware\VerifyWhatsAppSignature;
use Illuminate\Support\Facades\Route;

Route::prefix('webhooks')->group(function () {
    Route::get('whatsapp', [WhatsAppWebhookController::class, 'verify']);
    Route::post('whatsapp', [WhatsAppWebhookController::class, 'receive'])
        ->middleware(VerifyWhatsAppSignature::class);
});
