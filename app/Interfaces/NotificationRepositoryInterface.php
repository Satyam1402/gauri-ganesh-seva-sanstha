<?php

namespace App\Interfaces;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Read/write access to a user's in-app notifications. Every method is
 * scoped to the given user AND to the notification classes that user is
 * currently authorised to see (NotificationCatalog::visibleClassesFor).
 */
interface NotificationRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters  status: unread|read, category: NotificationCategory value
     */
    public function paginateFor(User $user, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * @return Collection<int, DatabaseNotification>
     */
    public function recentFor(User $user, int $limit = 6): Collection;

    public function unreadCountFor(User $user): int;

    public function findFor(User $user, string $id): ?DatabaseNotification;

    public function markAsRead(DatabaseNotification $notification): void;

    /**
     * @return int Number of notifications marked read.
     */
    public function markAllAsReadFor(User $user): int;

    public function delete(DatabaseNotification $notification): void;

    /**
     * @return int Number of notifications deleted.
     */
    public function deleteReadFor(User $user): int;
}
