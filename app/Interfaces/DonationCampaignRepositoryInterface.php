<?php

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface DonationCampaignRepositoryInterface extends RepositoryInterface
{
    /**
     * Filtered, sorted, paginated listing for the admin CRUD screen.
     *
     * @param  array<string, mixed>  $filters
     */
    public function adminSearch(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * All campaigns ordered by display order (admin selects, reorder screen).
     */
    public function allOrdered(): Collection;

    /**
     * Paginated active campaigns for the public listing, cached.
     *
     * @param  array<string, mixed>  $filters
     */
    public function activePaginated(array $filters, int $perPage = 9): LengthAwarePaginator;

    /**
     * Active campaigns available on the donate form's campaign selector.
     */
    public function activeOrdered(): Collection;

    /**
     * Featured active campaigns, cached.
     */
    public function featuredList(int $limit = 3): Collection;

    /**
     * Persist a new display order. $orderedIds is the full list of campaign
     * ids in the desired order.
     *
     * @param  list<int>  $orderedIds
     */
    public function reorder(array $orderedIds): void;
}
