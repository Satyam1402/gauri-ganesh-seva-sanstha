<?php

namespace App\Interfaces;

use Illuminate\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface extends RepositoryInterface
{
    /**
     * Paginate users, optionally filtered by a name/email search term.
     */
    public function search(?string $term, int $perPage = 15): LengthAwarePaginator;
}
