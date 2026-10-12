<?php

namespace App\Notifications;

use App\Models\WhatsAppConversation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WhatsAppConversationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public WhatsAppConversation $conversation,
        public string $event,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $this->conversation->loadMissing('contact');
        $contact = $this->conversation->contact?->displayName() ?: $this->conversation->contact?->phone ?: 'Unknown contact';

        return [
            'title' => $this->event === 'assigned' ? 'WhatsApp chat assigned' : 'New WhatsApp chat',
            'body' => $this->event === 'assigned'
                ? "{$contact} was assigned to you."
                : "{$contact} is waiting to be assigned.",
            'icon' => 'heroicon-o-chat-bubble-left-right',
            'color' => 'info',
            'actions' => [[
                'name' => 'open',
                'label' => $this->event === 'assigned' ? 'Open chat' : 'Assign chat',
                'url' => route('admin.whatsapp.show', $this->conversation),
                'color' => 'primary',
            ]],
            'whatsapp_conversation_id' => $this->conversation->id,
            'event' => $this->event,
        ];
    }
}
