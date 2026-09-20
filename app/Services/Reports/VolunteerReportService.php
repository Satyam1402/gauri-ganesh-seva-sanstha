<?php

namespace App\Services\Reports;

use App\Enums\VolunteerApplicationStatus;
use App\Models\VolunteerApplication;
use App\Services\Reports\Concerns\BucketsByPeriod;
use App\Support\Reports\DateRange;
use Illuminate\Database\Eloquent\Builder;

/**
 * Volunteer application aggregates. Only counts by status, period, city,
 * state and area of interest are produced — never names, contact details
 * or documents.
 */
class VolunteerReportService
{
    use BucketsByPeriod;

    public const TOP_LOCATIONS = 10;

    /**
     * @param  array<string, mixed>  $filters  status, state
     */
    public function report(DateRange $range, array $filters = []): array
    {
        return [
            'summary' => $this->summary($range, $filters),
            'trend' => $this->series($this->base($range, $filters), 'created_at', $range),
            'byState' => $this->byColumn('state', $range, $filters),
            'byCity' => $this->byColumn('city', $range, $filters),
            'byInterest' => $this->byInterest($range, $filters),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function trend(DateRange $range, array $filters = []): array
    {
        return $this->series($this->base($range, $filters), 'created_at', $range);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function summary(DateRange $range, array $filters = []): array
    {
        $cases = collect(VolunteerApplicationStatus::cases())
            ->map(fn (VolunteerApplicationStatus $s) => "SUM(CASE WHEN status = '{$s->value}' THEN 1 ELSE 0 END) as {$s->value}")
            ->implode(', ');

        $row = $this->base($range, $filters)->selectRaw("COUNT(*) as total, {$cases}")->first();

        $summary = ['total' => (int) $row->total];
        foreach (VolunteerApplicationStatus::cases() as $status) {
            $summary[$status->value] = (int) $row->{$status->value};
        }

        return $summary;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function byColumn(string $column, DateRange $range, array $filters = []): array
    {
        return $this->base($range, $filters)
            ->selectRaw("{$column} as label, COUNT(*) as aggregate")
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->groupBy($column)
            ->orderByDesc('aggregate')
            ->limit(self::TOP_LOCATIONS)
            ->pluck('aggregate', 'label')
            ->map(fn ($n) => (int) $n)
            ->all();
    }

    /**
     * Areas of interest are stored as a JSON array per application, so the
     * distribution is counted in PHP from one lightweight column select.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function byInterest(DateRange $range, array $filters = []): array
    {
        $labels = config('volunteers.areas_of_interest', []);
        $counts = array_fill_keys(array_values($labels), 0);

        $this->base($range, $filters)->select('areas_of_interest')->chunk(500, function ($rows) use (&$counts, $labels) {
            foreach ($rows as $row) {
                foreach ((array) $row->areas_of_interest as $key) {
                    $label = $labels[$key] ?? ucfirst(str_replace('_', ' ', (string) $key));
                    $counts[$label] = ($counts[$label] ?? 0) + 1;
                }
            }
        });

        arsort($counts);

        return $counts;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return iterable<list<string|int>>
     */
    public function exportRows(DateRange $range, array $filters = []): iterable
    {
        foreach ($this->series($this->base($range, $filters), 'created_at', $range) as $period => $count) {
            yield ['Period', $period, $count];
        }

        foreach ($this->summary($range, $filters) as $status => $count) {
            yield ['Status', $status === 'total' ? 'Total' : VolunteerApplicationStatus::from($status)->label(), $count];
        }

        foreach ($this->byState($range, $filters) as $state => $count) {
            yield ['State', $state, $count];
        }

        foreach ($this->byInterest($range, $filters) as $interest => $count) {
            yield ['Area of interest', $interest, $count];
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    private function byState(DateRange $range, array $filters): array
    {
        return $this->byColumn('state', $range, $filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function base(DateRange $range, array $filters): Builder
    {
        [$from, $to] = $range->bounds();

        return VolunteerApplication::query()
            ->whereBetween('created_at', [$from, $to])
            ->when(! empty($filters['status']), fn (Builder $q) => $q->where('status', $filters['status']))
            ->when(! empty($filters['state']), fn (Builder $q) => $q->where('state', $filters['state']));
    }
}
