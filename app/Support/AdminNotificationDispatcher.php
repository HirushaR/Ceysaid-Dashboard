<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\LeadDatabaseNotification;

class AdminNotificationDispatcher
{
    public static function enabled(): bool
    {
        return (bool) config('notifications.database_enabled', true);
    }

    public static function send(User $user, AdminNotificationMessage $notification, ?int $leadId = null): void
    {
        if (self::enabled()) {
            $user->notify(new LeadDatabaseNotification($notification, $leadId));
        }
    }
}
