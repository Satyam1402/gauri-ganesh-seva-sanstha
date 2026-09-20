<?php

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface FaqRepositoryInterface extends RepositoryInterface
{
    /**
     * Filtered, sorted, paginated listing for the admin CRUD screen.
     *
     * @param  array<string, mixed>  $filters
     */
    public function adminSearch(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Published FAQs for the dedicated page, optionally narrowed to one
     * category slug and/or a search term. Search results are not cached;
     * browse results are.
     *
     * @param  array<string, mixed>  $filters
     */
    public function publicList(array $filters): Collection;

    /**
     * Published FAQs for a reusable section: featured-only and/or one
     * category, limited. Cached.
     */
    public function sectionList(?string $categorySlug = null, bool $featuredOnly = false, int $limit = 6): Collection;

    /**
     * Counters for the admin statistics cards.
     *
     * @return array<string, int>
     */
    public function statistics(): array;

    /**
     * Soft-delete a batch of FAQs. Returns the number deleted.
     *
     * @param  list<int>  $ids
     */
    public function bulkDelete(array $ids): int;

    /**
     * Set the status for a batch of FAQs. Returns the number updated.
     *
     * @param  list<int>  $ids
     */
    public function bulkUpdateStatus(array $ids, string $status): int;
}
