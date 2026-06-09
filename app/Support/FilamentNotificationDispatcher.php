<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\LeadDatabaseNotification;
use Filament\Notifications\Events\DatabaseNotificationsSent;
use Filament\Notifications\Notification as FilamentNotification;

class FilamentNotificationDispatcher
{
    public static function enabled(): bool
    {
        return (bool) config('notifications.filament_database_enabled', false);
    }

    public static function send(User $user, FilamentNotification $notification, ?int $leadId = null): void
    {
        if (! self::enabled()) {
            return;
        }

        $user->notify(new LeadDatabaseNotification($notification, $leadId));
        event(new DatabaseNotificationsSent($user));
    }
}
