<?php

namespace App\Contracts;

use App\Enums\NotificationCategory;

/**
 * Extension point for per-recipient notification preferences.
 *
 * Nothing implements this yet — when preferences arrive (e.g. an admin
 * opting out of email for enquiries, or a donor opting into SMS) the
 * notifiable model implements this contract and the ChannelResolver
 * honours it automatically. Notification classes never need to change.
 */
interface HasNotificationPreferences
{
    /**
     * Channels this recipient wants for a category, or null to accept the
     * notification's defaults. Returned channels are still intersected with
     * the channels the notification supports and the site has enabled.
     *
     * @return list<string>|null
     */
    public function preferredNotificationChannels(?NotificationCategory $category): ?array;
}
