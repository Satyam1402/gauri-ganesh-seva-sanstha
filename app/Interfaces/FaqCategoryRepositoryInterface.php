<?php

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Collection;

interface FaqCategoryRepositoryInterface extends RepositoryInterface
{
    /**
     * All categories in display order with FAQ counts, for the admin listing.
     */
    public function allOrdered(): Collection;

    /**
     * Active categories in display order with published-FAQ counts, cached
     * for the public page's filter tabs.
     */
    public function activeOrdered(): Collection;

    /**
     * Persist a new display order for a set of category ids.
     *
     * @param  list<int>  $orderedIds
     */
    public function reorder(array $orderedIds): void;
}
