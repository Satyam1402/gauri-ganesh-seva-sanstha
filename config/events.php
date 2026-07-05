<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Event Notifications
    |--------------------------------------------------------------------------
    |
    | Address that receives a notification email for every new event
    | registration. Defaults to the general sender address when no
    | dedicated inbox is configured.
    |
    */

    'admin_notification_email' => env('EVENT_ADMIN_EMAIL', env('MAIL_FROM_ADDRESS')),

];
