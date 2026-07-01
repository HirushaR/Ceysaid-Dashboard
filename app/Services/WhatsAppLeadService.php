<?php

namespace App\Services;

use App\Enums\LeadStatus;
use App\Enums\Platform;
use App\Models\Lead;
use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Traits\SendsLeadNotifications;

class WhatsAppLeadService
{
    use SendsLeadNotifications;

    public function createFromConversation(WhatsAppConversation $conversation, ?User $user = null): Lead
    {
        $user ??= auth()->user();

        $conversation->loadMissing(['contact', 'messages']);

        $contact = $conversation->contact;
        $firstInbound = $conversation->messages()
            ->where('direction', 'inbound')
            ->orderBy('id')
            ->first();

        $attributes = [
            'customer_name' => $contact?->displayName() ?? 'WhatsApp contact',
            'platform' => Platform::WHATSAPP->value,
            'meta_ad_id' => $conversation->referral_source_id,
            'meta_ctwa_clid' => $conversation->referral_ctwa_clid,
            'contact_method' => 'whatsapp',
            'contact_value' => $contact?->phone,
            'message' => $firstInbound?->body ?? $conversation->last_message_preview,
            'status' => LeadStatus::NEW->value,
            'created_by' => $user?->id,
        ];

        if ($user && $user->isSales()) {
            $attributes['assigned_to'] = $user->id;
            $attributes['status'] = LeadStatus::ASSIGNED_TO_SALES->value;
        } elseif ($user) {
            $attributes['assigned_to'] = $user->id;
        }

        $lead = Lead::create($attributes);

        $conversation->update(['lead_id' => $lead->id]);

        $this->sendLeadCreatedNotifications($lead->fresh(['assignedUser']));

        return $lead;
    }
}
