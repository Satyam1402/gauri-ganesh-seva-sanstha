<?php

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Collection;

interface HomeSectionRepositoryInterface extends RepositoryInterface
{
    /**
     * All sections in display order, for the admin CMS listing.
     */
    public function allOrdered(): Collection;

    /**
     * Active sections in display order with buttons/items/media eager loaded,
     * cached for the public homepage.
     */
    public function activeForHomepage(): Collection;

    /**
     * Persist a new display order for a set of section ids.
     *
     * @param  list<int>  $orderedIds
     */
    public function reorder(array $orderedIds): void;
}
