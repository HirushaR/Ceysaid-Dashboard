<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Filament database notifications
    |--------------------------------------------------------------------------
    |
    | When false, the admin bell is hidden and lead notification broadcasts
    | are not sent (stops DatabaseNotificationsSent jobs on the queue).
    |
    */

    'filament_database_enabled' => env('FILAMENT_DATABASE_NOTIFICATIONS_ENABLED', false),

];
