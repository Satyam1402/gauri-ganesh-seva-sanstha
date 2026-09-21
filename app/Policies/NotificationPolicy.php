<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Notifications\NotificationCatalog;
use Illuminate\Notifications\DatabaseNotification;

/**
 * In-app notifications belong to the user they were delivered to, and are
 * only visible while that user still holds the permission of the
 * notification's category.
 */
class NotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DatabaseNotification $notification): bool
    {
        return $this->owns($user, $notification);
    }

    public function update(User $user, DatabaseNotification $notification): bool
    {
        return $this->owns($user, $notification);
    }

    public function delete(User $user, DatabaseNotification $notification): bool
    {
        return $this->owns($user, $notification);
    }

    private function owns(User $user, DatabaseNotification $notification): bool
    {
        return $notification->notifiable_type === $user->getMorphClass()
            && (string) $notification->notifiable_id === (string) $user->getKey()
            && in_array($notification->type, NotificationCatalog::visibleClassesFor($user), true);
    }
}
