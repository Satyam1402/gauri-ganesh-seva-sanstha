<?php

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Collection;

interface ActivityCategoryRepositoryInterface extends RepositoryInterface
{
    /**
     * All categories in display order, for the admin listing.
     */
    public function allOrdered(): Collection;

    /**
     * Active categories in display order, cached for public filter menus.
     */
    public function activeOrdered(): Collection;

    /**
     * Persist a new display order for a set of category ids.
     *
     * @param  list<int>  $orderedIds
     */
    public function reorder(array $orderedIds): void;
}
