<?php

namespace App\Repositories;

use App\Enums\NotificationCategory;
use App\Interfaces\NotificationRepositoryInterface;
use App\Models\User;
use App\Support\Notifications\NotificationCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationRepository implements NotificationRepositoryInterface
{
    public function paginateFor(User $user, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->visible($user);

        match ($filters['status'] ?? null) {
            'unread' => $query->whereNull('read_at'),
            'read' => $query->whereNotNull('read_at'),
            default => null,
        };

        $category = NotificationCategory::tryFrom((string) ($filters['category'] ?? ''));

        if ($category !== null) {
            // Intersect with the visible classes so an unauthorised category
            // filter yields an empty page rather than leaking rows.
            $query->whereIn('type', array_intersect(
                NotificationCatalog::classesFor($category),
                NotificationCatalog::visibleClassesFor($user),
            ));
        }

        return $query->latest()->paginate($perPage)->withQueryString();
    }

    public function recentFor(User $user, int $limit = 6): Collection
    {
        return $this->visible($user)->latest()->limit($limit)->get();
    }

    public function unreadCountFor(User $user): int
    {
        return $this->visible($user)->whereNull('read_at')->count();
    }

    public function findFor(User $user, string $id): ?DatabaseNotification
    {
        return $this->visible($user)->whereKey($id)->first();
    }

    public function markAsRead(DatabaseNotification $notification): void
    {
        $notification->markAsRead();
    }

    public function markAllAsReadFor(User $user): int
    {
        return $this->visible($user)->whereNull('read_at')->update(['read_at' => now()]);
    }

    public function delete(DatabaseNotification $notification): void
    {
        $notification->delete();
    }

    public function deleteReadFor(User $user): int
    {
        return $this->visible($user)->whereNotNull('read_at')->delete();
    }

    /**
     * The user's own notifications, restricted to the classes their
     * current permissions allow. `type` is a plain indexed column, so this
     * stays driver-agnostic (no JSON path queries).
     *
     * @return Builder<DatabaseNotification>
     */
    private function visible(User $user): Builder
    {
        return $user->notifications()
            ->getQuery()
            ->whereIn('type', NotificationCatalog::visibleClassesFor($user));
    }
}
