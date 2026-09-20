<?php

namespace App\Services\Reports\Concerns;

use App\Support\Reports\DateRange;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;

/**
 * Shared time-series helper: one GROUP BY query per series, bucketed by
 * day, month or year depending on how wide the window is, with empty
 * buckets filled in so charts never skip periods.
 */
trait BucketsByPeriod
{
    /**
     * @param  Builder|QueryBuilder  $query  Already filtered to the date window.
     * @param  string  $column  Date column to bucket on.
     * @param  string  $aggregate  SQL aggregate, e.g. "COUNT(*)" or "COALESCE(SUM(amount), 0)".
     * @return array<string, float|int>  [bucket label => value], oldest first, gaps filled.
     */
    protected function series(Builder|QueryBuilder $query, string $column, DateRange $range, string $aggregate = 'COUNT(*)'): array
    {
        $granularity = $range->granularity();
        $driver = $query->getConnection()->getDriverName();

        // Bucket key format: SQL strftime/DATE_FORMAT token and the matching PHP date() format.
        [$format, $phpFormat] = match ($granularity) {
            'year' => ['%Y', 'Y'],
            'month' => ['%Y-%m', 'Y-m'],
            default => ['%Y-%m-%d', 'Y-m-d'],
        };

        // SQLite (tests) and MySQL format dates differently.
        $bucket = $driver === 'sqlite'
            ? "strftime('{$format}', {$column})"
            : "DATE_FORMAT({$column}, '{$format}')";

        $rows = $query->selectRaw("{$bucket} as bucket, {$aggregate} as aggregate")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->pluck('aggregate', 'bucket');

        $keys = match ($granularity) {
            'year' => $range->yearKeys(),
            'month' => $range->monthKeys(),
            default => $range->dayKeys(),
        };

        $filled = [];
        foreach ($keys as $key) {
            $value = $rows[$key] ?? 0;
            $filled[$key] = is_numeric($value) && str_contains((string) $value, '.') ? (float) $value : (int) $value;
        }

        // "All time" is open-ended on both sides; drop the empty years at the edges.
        if ($range->preset === 'all') {
            $filled = $this->trimEmptyEdges($filled, now()->format($phpFormat));
        }

        $labelled = [];
        foreach ($filled as $key => $value) {
            $labelled[$this->bucketLabel((string) $key, $granularity)] = $value;
        }

        return $labelled;
    }

    /**
     * @param  array<string, float|int>  $buckets
     * @param  string  $currentKey  Bucket key for today, kept when everything is empty.
     * @return array<string, float|int>
     */
    private function trimEmptyEdges(array $buckets, string $currentKey): array
    {
        $keys = array_keys(array_filter($buckets, fn ($v) => $v != 0));

        // Nothing recorded at all: show just the current period as zero.
        if ($keys === []) {
            return array_key_exists($currentKey, $buckets) ? [$currentKey => 0] : array_slice($buckets, -1, 1, true);
        }

        $first = array_search($keys[0], array_keys($buckets), true);
        $last = array_search(end($keys), array_keys($buckets), true);

        return array_slice($buckets, $first, $last - $first + 1, true);
    }

    private function bucketLabel(string $key, string $granularity): string
    {
        return match ($granularity) {
            'year' => $key,
            'month' => Carbon::createFromFormat('Y-m', $key)->format('M Y'),
            default => Carbon::createFromFormat('Y-m-d', $key)->format('d M'),
        };
    }
}
