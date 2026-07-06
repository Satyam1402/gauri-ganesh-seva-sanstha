<?php

namespace App\Interfaces;

use App\Enums\EnquiryStatus;
use App\Models\ContactEnquiry;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\LazyCollection;

interface ContactEnquiryRepositoryInterface extends RepositoryInterface
{
    /**
     * Filtered, paginated listing for the admin enquiries screen.
     *
     * @param  array<string, mixed>  $filters
     */
    public function adminSearch(array $filters, int $perPage = 20): LengthAwarePaginator;

    /**
     * Memory-safe cursor over the filtered enquiries for exports.
     *
     * @param  array<string, mixed>  $filters
     * @return LazyCollection<int, ContactEnquiry>
     */
    public function exportCursor(array $filters): LazyCollection;

    /**
     * Enquiry counts per status for the admin dashboard cards.
     *
     * @return array<string, int>
     */
    public function countsByStatus(): array;

    /**
     * Load the given enquiries for a bulk action.
     *
     * @param  list<int>  $ids
     * @return Collection<int, ContactEnquiry>
     */
    public function findMany(array $ids): Collection;

    /**
     * Apply a status to many enquiries in one statement.
     *
     * @param  list<int>  $ids
     * @return int Number of enquiries updated.
     */
    public function bulkUpdateStatus(array $ids, EnquiryStatus $status): int;
}
