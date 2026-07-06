<?php

namespace App\Interfaces;

use App\Models\VolunteerApplication;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\LazyCollection;

interface VolunteerApplicationRepositoryInterface extends RepositoryInterface
{
    /**
     * Filtered, paginated listing for the admin applications screen.
     *
     * @param  array<string, mixed>  $filters
     */
    public function adminSearch(array $filters, int $perPage = 20): LengthAwarePaginator;

    /**
     * Memory-safe cursor over the filtered applications for exports.
     *
     * @param  array<string, mixed>  $filters
     * @return LazyCollection<int, VolunteerApplication>
     */
    public function exportCursor(array $filters): LazyCollection;

    /**
     * Application counts per status for the admin statistics cards.
     *
     * @return array<string, int>
     */
    public function countsByStatus(): array;

    /**
     * Load the given applications for a bulk action.
     *
     * @param  list<int>  $ids
     * @return Collection<int, VolunteerApplication>
     */
    public function findMany(array $ids): Collection;
}
