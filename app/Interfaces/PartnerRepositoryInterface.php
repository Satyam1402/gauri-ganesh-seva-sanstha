<?php

namespace App\Interfaces;

use App\Models\Partner;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface PartnerRepositoryInterface extends RepositoryInterface
{
    /**
     * Filtered, sorted, paginated listing for the admin CRUD screen.
     *
     * @param  array<string, mixed>  $filters
     */
    public function adminSearch(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Active partners for the dedicated page, optionally narrowed to one
     * type slug. Cached.
     */
    public function publicList(?string $typeSlug = null): Collection;

    /**
     * Active partners for a reusable section: featured-only and/or one
     * type, limited. Cached.
     */
    public function sectionList(?string $typeSlug = null, bool $featuredOnly = false, int $limit = 8): Collection;

    /**
     * Other active partners of the same type, for the detail page.
     */
    public function related(Partner $partner, int $limit = 4): Collection;

    /**
     * Counters for the admin statistics cards.
     *
     * @return array<string, int>
     */
    public function statistics(): array;

    /**
     * Soft-delete a batch of partners. Returns the number deleted.
     *
     * @param  list<int>  $ids
     */
    public function bulkDelete(array $ids): int;

    /**
     * Set the status for a batch of partners. Returns the number updated.
     *
     * @param  list<int>  $ids
     */
    public function bulkUpdateStatus(array $ids, string $status): int;
}
