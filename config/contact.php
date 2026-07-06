<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enquiry Notifications
    |--------------------------------------------------------------------------
    |
    | Address that receives a notification email for every new contact
    | enquiry. Defaults to the general sender address when no dedicated
    | inbox is configured.
    |
    */

    'admin_notification_email' => env('CONTACT_ADMIN_EMAIL', env('MAIL_FROM_ADDRESS')),

];
