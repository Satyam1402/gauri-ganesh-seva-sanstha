<?php

namespace App\Interfaces;

use App\Models\Event;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface EventRepositoryInterface extends RepositoryInterface
{
    /**
     * Filtered, sorted, paginated listing for the admin CRUD screen.
     *
     * @param  array<string, mixed>  $filters
     */
    public function adminSearch(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Filtered, paginated listing of public events for the website. The
     * "when" filter switches between upcoming and past events.
     *
     * @param  array<string, mixed>  $filters
     */
    public function publicPaginated(array $filters, int $perPage = 12): LengthAwarePaginator;

    /**
     * Next published upcoming events, cached.
     */
    public function upcomingList(int $limit = 3): Collection;

    /**
     * Featured published upcoming events, cached.
     */
    public function featuredList(int $limit = 3): Collection;

    /**
     * Other public events in the same category as the given event.
     */
    public function related(Event $event, int $limit = 3): Collection;

    /**
     * Counters for the admin statistics cards.
     *
     * @return array<string, int>
     */
    public function statistics(): array;

    /**
     * Soft-delete a batch of events. Returns the number deleted.
     *
     * @param  list<int>  $ids
     */
    public function bulkDelete(array $ids): int;

    /**
     * Set the status for a batch of events. Returns the number updated.
     *
     * @param  list<int>  $ids
     */
    public function bulkUpdateStatus(array $ids, string $status): int;
}
