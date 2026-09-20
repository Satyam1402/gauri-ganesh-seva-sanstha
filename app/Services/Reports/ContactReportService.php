<?php

namespace App\Services\Reports;

use App\Enums\EnquiryCategory;
use App\Enums\EnquiryStatus;
use App\Models\ContactEnquiry;
use App\Services\Reports\Concerns\BucketsByPeriod;
use App\Support\Reports\DateRange;
use Illuminate\Database\Eloquent\Builder;

/**
 * Contact enquiry aggregates — counts by status, category and period.
 * Names, emails, messages and admin notes are never selected.
 */
class ContactReportService
{
    use BucketsByPeriod;

    /**
     * @param  array<string, mixed>  $filters  category, status
     */
    public function report(DateRange $range, array $filters = []): array
    {
        return [
            'summary' => $this->summary($range, $filters),
            'byCategory' => $this->byCategory($range, $filters),
            'trend' => $this->series($this->base($range, $filters), 'created_at', $range),
            'responseTime' => $this->averageResponseHours($range, $filters),
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
        $cases = collect(EnquiryStatus::cases())
            ->map(fn (EnquiryStatus $s) => "SUM(CASE WHEN status = '{$s->value}' THEN 1 ELSE 0 END) as {$s->value}")
            ->implode(', ');

        $row = $this->base($range, $filters)->selectRaw("COUNT(*) as total, {$cases}")->first();

        $summary = ['total' => (int) $row->total];
        foreach (EnquiryStatus::cases() as $status) {
            $summary[$status->value] = (int) $row->{$status->value};
        }

        return $summary;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function byCategory(DateRange $range, array $filters = []): array
    {
        $rows = $this->base($range, $filters)
            ->selectRaw('category, COUNT(*) as aggregate')
            ->groupBy('category')
            ->pluck('aggregate', 'category');

        $result = [];
        foreach (EnquiryCategory::cases() as $category) {
            if (isset($rows[$category->value])) {
                $result[$category->label()] = (int) $rows[$category->value];
            }
        }
        arsort($result);

        return $result;
    }

    /**
     * Mean hours from receipt to first reply for enquiries that were
     * replied to — null when nothing has been answered yet.
     *
     * @param  array<string, mixed>  $filters
     */
    public function averageResponseHours(DateRange $range, array $filters = []): ?float
    {
        $query = $this->base($range, $filters)->whereNotNull('replied_at');

        $seconds = $query->getConnection()->getDriverName() === 'sqlite'
            ? $query->selectRaw('AVG((julianday(replied_at) - julianday(created_at)) * 86400) as seconds')->value('seconds')
            : $query->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, replied_at)) as seconds')->value('seconds');

        return $seconds === null ? null : round((float) $seconds / 3600, 1);
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
            yield ['Status', $status === 'total' ? 'Total' : EnquiryStatus::from($status)->label(), $count];
        }

        foreach ($this->byCategory($range, $filters) as $category => $count) {
            yield ['Category', $category, $count];
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function base(DateRange $range, array $filters): Builder
    {
        return ContactEnquiry::query()
            ->whereBetween('created_at', $range->bounds())
            ->when(! empty($filters['category']), fn (Builder $q) => $q->where('category', $filters['category']))
            ->when(! empty($filters['status']), fn (Builder $q) => $q->where('status', $filters['status']));
    }
}
