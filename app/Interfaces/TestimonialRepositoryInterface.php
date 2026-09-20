<?php

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface TestimonialRepositoryInterface extends RepositoryInterface
{
    /**
     * Filtered, sorted, paginated listing for the admin CRUD screen.
     *
     * @param  array<string, mixed>  $filters
     */
    public function adminSearch(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Paginated public listing for the dedicated testimonials page,
     * optionally narrowed to one type. Cached per filter + page.
     *
     * @param  array<string, mixed>  $filters
     */
    public function publicPaginated(array $filters, int $perPage = 12): LengthAwarePaginator;

    /**
     * Published testimonials for a reusable section (homepage, about,
     * activity/campaign pages). Featured-only and per-type variants share
     * this one cached method.
     */
    public function sectionList(?string $type = null, bool $featuredOnly = false, int $limit = 3): Collection;

    /**
     * Published testimonials linked to a specific activity or campaign.
     */
    public function forRelated(Model $related, int $limit = 3): Collection;

    /**
     * Published-per-type counts for the public page's filter tabs.
     *
     * @return array<string, int>
     */
    public function publicTypeCounts(): array;

    /**
     * Counters for the admin statistics cards.
     *
     * @return array<string, int>
     */
    public function statistics(): array;

    /**
     * Soft-delete a batch of testimonials. Returns the number deleted.
     *
     * @param  list<int>  $ids
     */
    public function bulkDelete(array $ids): int;

    /**
     * Set the status for a batch of testimonials. Returns the number updated.
     *
     * @param  list<int>  $ids
     * @param  array<string, mixed>  $extra  Additional columns to set alongside the status.
     */
    public function bulkUpdateStatus(array $ids, string $status, array $extra = []): int;
}
