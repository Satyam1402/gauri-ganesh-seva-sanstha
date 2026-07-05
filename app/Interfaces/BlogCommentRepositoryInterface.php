<?php

namespace App\Interfaces;

use Illuminate\Pagination\LengthAwarePaginator;

interface BlogCommentRepositoryInterface extends RepositoryInterface
{
    /**
     * Filtered, paginated listing for the admin moderation screen.
     *
     * @param  array<string, mixed>  $filters
     */
    public function adminSearch(array $filters, int $perPage = 20): LengthAwarePaginator;

    /**
     * Comment counts per status for the moderation header cards.
     *
     * @return array<string, int>
     */
    public function countsByStatus(): array;
}
