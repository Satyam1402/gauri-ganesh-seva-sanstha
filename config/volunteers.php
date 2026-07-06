<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Volunteer Notifications
    |--------------------------------------------------------------------------
    |
    | Address that receives a notification email for every new volunteer
    | application. Defaults to the general sender address when no
    | dedicated inbox is configured.
    |
    */

    'admin_notification_email' => env('VOLUNTEER_ADMIN_EMAIL', env('MAIL_FROM_ADDRESS')),

    /*
    |--------------------------------------------------------------------------
    | Areas of Interest
    |--------------------------------------------------------------------------
    |
    | The programme areas an applicant can volunteer for. Keys are stored in
    | the database (JSON array), so treat them as stable identifiers — add
    | new areas freely but never rename existing keys.
    |
    */

    'areas_of_interest' => [
        'food_distribution' => 'Food Distribution',
        'education_teaching' => 'Education & Teaching',
        'medical_health' => 'Medical & Health Camps',
        'fundraising' => 'Fundraising',
        'event_management' => 'Event Management',
        'media_content' => 'Social Media & Content',
        'administration' => 'Administration & Office Support',
        'field_work' => 'Field Work & Community Outreach',
    ],

];
