<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Delivery Channels
    |--------------------------------------------------------------------------
    |
    | Every App\Notifications\BaseNotification asks the ChannelResolver which
    | channels to use; the resolver keeps only the channels a notification
    | supports AND that are switched on here. To add SMS or WhatsApp later:
    | enable the channel, register its channel class in
    | NotificationServiceProvider, and add routeNotificationForSms() /
    | routeNotificationForWhatsapp() to the notifiable models. No existing
    | notification class needs to change.
    |
    */

    'channels' => [
        'mail' => (bool) env('NOTIFICATIONS_MAIL_ENABLED', true),
        'database' => (bool) env('NOTIFICATIONS_DATABASE_ENABLED', true),
        'sms' => false,
        'whatsapp' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Queueing
    |--------------------------------------------------------------------------
    |
    | Outgoing notifications are queued so visitors never wait for SMTP.
    | Failed deliveries are retried with the back-off below, then land in
    | the failed_jobs table (php artisan queue:failed / queue:retry).
    |
    */

    'queue' => env('NOTIFICATIONS_QUEUE', 'default'),
    'tries' => (int) env('NOTIFICATIONS_TRIES', 3),
    'backoff' => [60, 300, 900],

    /*
    |--------------------------------------------------------------------------
    | Admin Notification Centre
    |--------------------------------------------------------------------------
    */

    'dropdown_limit' => 6,
    'per_page' => 20,

    /*
    |--------------------------------------------------------------------------
    | Team Inboxes (optional)
    |--------------------------------------------------------------------------
    |
    | Internal alerts always go to the users who hold the module permission.
    | A module may additionally copy a shared mailbox (e.g. donations@…) —
    | those addresses live in config/donations.php, events.php, volunteers.php
    | and contact.php as admin_notification_email and are only used when set.
    |
    */

];
