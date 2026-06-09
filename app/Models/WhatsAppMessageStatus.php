<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppMessageStatus extends Model
{
    protected $table = 'whatsapp_message_statuses';

    protected $fillable = [
        'whatsapp_message_id',
        'status',
        'status_at',
        'raw_payload',
    ];

    protected $casts = [
        'status_at' => 'datetime',
        'raw_payload' => 'array',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(WhatsAppMessage::class, 'whatsapp_message_id');
    }
}
