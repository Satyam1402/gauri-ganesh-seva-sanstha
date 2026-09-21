<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enquiry Notifications
    |--------------------------------------------------------------------------
    |
    | Address that receives a notification email for every new contact
    | enquiry, in addition to the admin users who hold "manage contact
    | messages". Optional — leave empty to notify users only.
    |
    */

    'admin_notification_email' => env('CONTACT_ADMIN_EMAIL'),

];
