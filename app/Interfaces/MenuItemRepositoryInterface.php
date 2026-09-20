<?php

namespace App\Interfaces;

use App\Enums\MenuLocation;
use Illuminate\Database\Eloquent\Collection;

interface MenuItemRepositoryInterface extends RepositoryInterface
{
    /**
     * Active top-level items (with active children) for a public
     * location, cached forever and busted on write.
     */
    public function tree(MenuLocation $location): Collection;

    /**
     * Every item for a location (active or not) with children, for the
     * admin editor.
     */
    public function allForLocation(MenuLocation $location): Collection;

    /**
     * Persist a new order for a set of sibling ids.
     *
     * @param  list<int>  $orderedIds
     */
    public function reorder(array $orderedIds): void;
}
