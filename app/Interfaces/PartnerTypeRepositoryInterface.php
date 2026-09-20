<?php

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Collection;

interface PartnerTypeRepositoryInterface extends RepositoryInterface
{
    /**
     * All types in display order with partner counts, for the admin listing.
     */
    public function allOrdered(): Collection;

    /**
     * Active types in display order with active-partner counts, cached
     * for the public page's grouping and filter tabs.
     */
    public function activeOrdered(): Collection;

    /**
     * Persist a new display order for a set of type ids.
     *
     * @param  list<int>  $orderedIds
     */
    public function reorder(array $orderedIds): void;
}
