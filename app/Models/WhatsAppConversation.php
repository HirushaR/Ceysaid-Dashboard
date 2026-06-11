<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WhatsAppConversation extends Model
{
    protected $table = 'whatsapp_conversations';

    protected $fillable = [
        'whatsapp_contact_id',
        'lead_id',
        'assigned_to',
        'assigned_at',
        'referral_source_id',
        'referral_source_type',
        'referral_source_url',
        'referral_headline',
        'referral_ctwa_clid',
        'last_message_at',
        'last_message_preview',
        'unread_count',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'assigned_at' => 'datetime',
        'unread_count' => 'integer',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(WhatsAppContact::class, 'whatsapp_contact_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function scopeUnassigned(Builder $query): Builder
    {
        return $query->whereNull('assigned_to');
    }

    public function scopeAssigned(Builder $query): Builder
    {
        return $query->whereNotNull('assigned_to');
    }

    public function scopeAssignedToUser(Builder $query, int $userId): Builder
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeOrderByRecentActivity(Builder $query): Builder
    {
        return $query
            ->orderByRaw('(unread_count > 0) DESC')
            ->orderByRaw('COALESCE(last_message_at, updated_at, created_at) DESC')
            ->orderByDesc('id');
    }

    public function syncFromLatestMessage(): void
    {
        $latest = $this->messages()
            ->orderByDesc('sent_at')
            ->orderByDesc('id')
            ->first();

        if (! $latest) {
            return;
        }

        $preview = $latest->body
            ?: ($latest->media_filename ?: '['.ucfirst($latest->type).']');

        $this->update([
            'last_message_at' => $latest->sent_at ?? $latest->created_at,
            'last_message_preview' => Str::limit($preview, 120),
        ]);
    }

    public function isAssigned(): bool
    {
        return $this->assigned_to !== null;
    }

    public function isAssignedTo(?User $user): bool
    {
        return $user && $this->assigned_to === $user->id;
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class, 'whatsapp_conversation_id')->orderBy('created_at');
    }

    public function markAsRead(): void
    {
        if ($this->unread_count > 0) {
            $this->update(['unread_count' => 0]);
        }
    }

    public function incrementUnread(): void
    {
        $this->increment('unread_count');
    }

    public function hasAdAttribution(): bool
    {
        return filled($this->referral_source_id) || filled($this->referral_source_url);
    }

    public function adUrl(): ?string
    {
        if (filled($this->referral_source_url)) {
            return $this->referral_source_url;
        }

        if (filled($this->referral_source_id)) {
            return 'https://www.facebook.com/adsmanager/manage/ads?selected_ad_ids='.$this->referral_source_id;
        }

        return null;
    }

    public function adImageUrl(): ?string
    {
        $referral = $this->messages()
            ->whereNotNull('referral')
            ->orderBy('id')
            ->value('referral');

        if (! is_array($referral)) {
            return null;
        }

        return $referral['image_url'] ?? $referral['thumbnail_url'] ?? null;
    }
}
