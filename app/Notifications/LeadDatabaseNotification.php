<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Support\AdminNotificationMessage;

class LeadDatabaseNotification extends Notification
{
    use Queueable;

    protected AdminNotificationMessage $notification;
    protected ?int $leadId;

    /**
     * Create a new notification instance.
     */
    public function __construct(AdminNotificationMessage $notification, ?int $leadId = null)
    {
        $this->notification = $notification;
        $this->leadId = $leadId;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [...$this->notification->toArray(), 'lead_id' => $this->leadId];
    }
}
