<?php

namespace App\Services;

use App\Enums\LeadStatus;
use App\Enums\Platform;
use App\Models\Lead;
use App\Models\WhatsAppConversation;

class WhatsAppLeadService
{
    public function createFromConversation(WhatsAppConversation $conversation): Lead
    {
        $conversation->loadMissing(['contact', 'messages']);

        $contact = $conversation->contact;
        $firstInbound = $conversation->messages()
            ->where('direction', 'inbound')
            ->orderBy('id')
            ->first();

        $lead = Lead::create([
            'customer_name' => $contact?->displayName() ?? 'WhatsApp contact',
            'platform' => Platform::WHATSAPP->value,
            'meta_ad_id' => $conversation->referral_source_id,
            'meta_ctwa_clid' => $conversation->referral_ctwa_clid,
            'contact_method' => 'whatsapp',
            'contact_value' => $contact?->phone,
            'message' => $firstInbound?->body ?? $conversation->last_message_preview,
            'status' => LeadStatus::NEW->value,
            'assigned_to' => $conversation->assigned_to,
        ]);

        $conversation->update(['lead_id' => $lead->id]);

        return $lead;
    }
}
