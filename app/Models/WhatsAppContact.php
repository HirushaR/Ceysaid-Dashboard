<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WhatsAppContact extends Model
{
    protected $table = 'whatsapp_contacts';

    protected $fillable = [
        'wa_id',
        'phone',
        'profile_name',
    ];

    public function conversations(): HasMany
    {
        return $this->hasMany(WhatsAppConversation::class, 'whatsapp_contact_id');
    }

    public function conversation(): HasOne
    {
        return $this->hasOne(WhatsAppConversation::class, 'whatsapp_contact_id');
    }

    public function displayName(): string
    {
        return $this->profile_name ?: $this->phone;
    }
}
