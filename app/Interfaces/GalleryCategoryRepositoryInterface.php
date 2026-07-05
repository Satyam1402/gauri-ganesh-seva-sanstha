<?php

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Collection;

interface GalleryCategoryRepositoryInterface extends RepositoryInterface
{
    /**
     * All categories with album counts, in display order (admin screens).
     */
    public function allOrdered(): Collection;

    /**
     * Active categories in display order, cached (public filters, selects).
     */
    public function activeOrdered(): Collection;

    /**
     * Persist a new display order. $orderedIds is the full id list in the
     * desired order.
     *
     * @param  list<int>  $orderedIds
     */
    public function reorder(array $orderedIds): void;
}
