<?php

namespace App\Support\Notifications;

use App\Contracts\HasNotificationPreferences;
use App\Models\User;
use App\Notifications\AdminNotification;
use App\Notifications\BaseNotification;

/**
 * Decides which channels a notification actually goes out on.
 *
 * Order of precedence: the channels the notification class supports →
 * intersected with config('notifications.channels') → intersected with the
 * recipient's own preferences (once a model implements
 * HasNotificationPreferences). The database channel is only ever used for
 * admin users; public recipients (donors, applicants…) never get in-app
 * rows because they have no admin account to read them from.
 */
class ChannelResolver
{
    /**
     * @return list<string>
     */
    public function channelsFor(mixed $notifiable, BaseNotification $notification): array
    {
        $channels = array_values(array_filter(
            $notification->supportedChannels(),
            fn (string $channel) => (bool) config("notifications.channels.{$channel}", false),
        ));

        if (! $notifiable instanceof User) {
            $channels = array_values(array_diff($channels, ['database']));
        }

        if ($notifiable instanceof HasNotificationPreferences) {
            $preferred = $notifiable->preferredNotificationChannels(
                $notification instanceof AdminNotification ? $notification::category() : null,
            );

            if ($preferred !== null) {
                $channels = array_values(array_intersect($channels, $preferred));
            }
        }

        return $channels;
    }
}
