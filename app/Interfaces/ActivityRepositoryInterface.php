<?php

namespace App\Interfaces;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface ActivityRepositoryInterface extends RepositoryInterface
{
    /**
     * Filtered, sorted, paginated listing for the admin CRUD screen.
     *
     * @param  array<string, mixed>  $filters
     */
    public function adminSearch(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Filtered, paginated listing of published activities for the public site.
     *
     * @param  array<string, mixed>  $filters
     */
    public function publishedPaginated(array $filters, int $perPage = 12): LengthAwarePaginator;

    /**
     * Most recently published activities, cached.
     */
    public function latest(int $limit = 6): Collection;

    /**
     * Featured published activities, cached.
     */
    public function featuredList(int $limit = 6): Collection;

    /**
     * Other published activities in the same category as the given activity.
     */
    public function related(Activity $activity, int $limit = 3): Collection;

    /**
     * Soft-delete a batch of activities. Returns the number deleted.
     *
     * @param  list<int>  $ids
     */
    public function bulkDelete(array $ids): int;

    /**
     * Set the status for a batch of activities. Returns the number updated.
     *
     * @param  list<int>  $ids
     */
    public function bulkUpdateStatus(array $ids, string $status): int;

    /**
     * Reassign a batch of activities to a category. Returns the number updated.
     *
     * @param  list<int>  $ids
     */
    public function bulkUpdateCategory(array $ids, int $categoryId): int;
}
