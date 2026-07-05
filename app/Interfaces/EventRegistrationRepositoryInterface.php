<?php

namespace App\Interfaces;

use App\Models\EventRegistration;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\LazyCollection;

interface EventRegistrationRepositoryInterface extends RepositoryInterface
{
    /**
     * Filtered, paginated listing for the admin registrations screen.
     *
     * @param  array<string, mixed>  $filters
     */
    public function adminSearch(array $filters, int $perPage = 20): LengthAwarePaginator;

    /**
     * Memory-safe cursor over the filtered registrations for exports.
     *
     * @param  array<string, mixed>  $filters
     * @return LazyCollection<int, EventRegistration>
     */
    public function exportCursor(array $filters): LazyCollection;

    /**
     * Registration counts per status for the admin statistics cards.
     *
     * @return array<string, int>
     */
    public function countsByStatus(): array;
}
