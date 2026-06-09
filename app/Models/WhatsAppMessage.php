<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppMessage extends Model
{
    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'whatsapp_conversation_id',
        'wamid',
        'direction',
        'type',
        'body',
        'referral',
        'media_id',
        'media_mime_type',
        'media_path',
        'status',
        'sent_by_user_id',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'referral' => 'array',
    ];

    public function fromAd(): bool
    {
        return filled($this->referral['source_id'] ?? null)
            && ($this->referral['source_type'] ?? null) === 'ad';
    }

    public function adUrl(): ?string
    {
        if (! is_array($this->referral)) {
            return null;
        }

        if (filled($this->referral['source_url'] ?? null)) {
            return $this->referral['source_url'];
        }

        if (filled($this->referral['source_id'] ?? null)) {
            return 'https://www.facebook.com/adsmanager/manage/ads?selected_ad_ids='.$this->referral['source_id'];
        }

        return null;
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsAppConversation::class, 'whatsapp_conversation_id');
    }

    public function sentByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }

    public function statuses(): HasMany
    {
        return $this->hasMany(WhatsAppMessageStatus::class, 'whatsapp_message_id');
    }

    public function isInbound(): bool
    {
        return $this->direction === 'inbound';
    }

    public function isOutbound(): bool
    {
        return $this->direction === 'outbound';
    }
}
