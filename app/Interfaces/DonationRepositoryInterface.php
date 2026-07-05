<?php

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\LazyCollection;

interface DonationRepositoryInterface extends RepositoryInterface
{
    /**
     * Filtered, sorted, paginated listing for the admin donations screen.
     *
     * @param  array<string, mixed>  $filters
     */
    public function adminSearch(array $filters, int $perPage = 20): LengthAwarePaginator;

    /**
     * Same filters as adminSearch but as a memory-safe cursor for exports.
     *
     * @param  array<string, mixed>  $filters
     */
    public function exportCursor(array $filters): LazyCollection;

    /**
     * Headline totals for the reports screen: total/count/average overall,
     * plus this-month and this-year figures (completed donations only).
     *
     * @return array<string, float|int>
     */
    public function revenueSummary(): array;

    /**
     * Completed donation totals per month for the trailing N months.
     * Returns [['month' => 'Jan 2026', 'total' => 12000.0, 'count' => 4], ...]
     * oldest first. Grouped in PHP so it works on MySQL and SQLite alike.
     *
     * @return list<array{month: string, total: float, count: int}>
     */
    public function monthlyTotals(int $months = 12): array;

    /**
     * Top donors by completed amount, grouped by email. Anonymous donations
     * are excluded to honour donor anonymity.
     */
    public function topDonors(int $limit = 10): BaseCollection;

    /**
     * Recent completed donations for dashboards/reports.
     */
    public function recentCompleted(int $limit = 10): Collection;
}
