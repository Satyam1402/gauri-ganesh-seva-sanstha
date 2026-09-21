<?php

use App\Providers\AppServiceProvider;
use App\Providers\NotificationServiceProvider;
use App\Providers\SettingsServiceProvider;

return [
    AppServiceProvider::class,
    SettingsServiceProvider::class,
    NotificationServiceProvider::class,
];
