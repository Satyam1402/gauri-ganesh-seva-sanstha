<?php

namespace App\Services\Reports;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Donation;
use App\Models\DonationCampaign;
use App\Services\Reports\Concerns\BucketsByPeriod;
use App\Support\Reports\DateRange;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Donation aggregates for the reports screen. Every method returns totals
 * and counts only — never donor names, emails, PAN numbers or addresses.
 */
class DonationReportService
{
    use BucketsByPeriod;

    /**
     * @param  array<string, mixed>  $filters  campaign (id), status, method
     */
    public function report(DateRange $range, array $filters = []): array
    {
        return [
            'summary' => $this->summary($range, $filters),
            'trend' => $this->series($this->base($range, $filters)->completed(), 'donated_at', $range, 'COALESCE(SUM(amount), 0)'),
            'countTrend' => $this->series($this->base($range, $filters), 'donated_at', $range),
            'byCampaign' => $this->byCampaign($range, $filters),
            'byStatus' => $this->byStatus($range, $filters),
            'byMethod' => $this->byMethod($range, $filters),
        ];
    }

    /**
     * Completed amount per period.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, float|int>
     */
    public function trend(DateRange $range, array $filters = []): array
    {
        return $this->series($this->base($range, $filters)->completed(), 'donated_at', $range, 'COALESCE(SUM(amount), 0)');
    }

    /**
     * One conditional-aggregate query for every headline number.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, float|int>
     */
    public function summary(DateRange $range, array $filters = []): array
    {
        $completed = PaymentStatus::Completed->value;
        $failed = PaymentStatus::Failed->value;
        $pending = PaymentStatus::Pending->value;
        $refunded = PaymentStatus::Refunded->value;
        $offline = "'".implode("','", array_map(fn (PaymentMethod $m) => $m->value, array_filter(PaymentMethod::cases(), fn (PaymentMethod $m) => $m->isOffline())))."'";

        $row = $this->base($range, $filters)->selectRaw(<<<SQL
            COUNT(*) as donations,
            COALESCE(SUM(CASE WHEN payment_status = '{$completed}' THEN amount END), 0) as total_amount,
            SUM(CASE WHEN payment_status = '{$completed}' THEN 1 ELSE 0 END) as completed_count,
            SUM(CASE WHEN payment_status = '{$failed}' THEN 1 ELSE 0 END) as failed_count,
            SUM(CASE WHEN payment_status = '{$pending}' THEN 1 ELSE 0 END) as pending_count,
            COALESCE(SUM(CASE WHEN payment_status = '{$pending}' THEN amount END), 0) as pending_amount,
            SUM(CASE WHEN payment_status = '{$refunded}' THEN 1 ELSE 0 END) as refunded_count,
            SUM(CASE WHEN payment_method IN ({$offline}) THEN 1 ELSE 0 END) as offline_count,
            COALESCE(SUM(CASE WHEN payment_method IN ({$offline}) AND payment_status = '{$completed}' THEN amount END), 0) as offline_amount,
            SUM(CASE WHEN payment_method NOT IN ({$offline}) THEN 1 ELSE 0 END) as online_count,
            COALESCE(SUM(CASE WHEN payment_method NOT IN ({$offline}) AND payment_status = '{$completed}' THEN amount END), 0) as online_amount,
            COALESCE(AVG(CASE WHEN payment_status = '{$completed}' THEN amount END), 0) as average_amount
        SQL)->first();

        return [
            'donations' => (int) $row->donations,
            'total_amount' => (float) $row->total_amount,
            'completed_count' => (int) $row->completed_count,
            'failed_count' => (int) $row->failed_count,
            'pending_count' => (int) $row->pending_count,
            'pending_amount' => (float) $row->pending_amount,
            'refunded_count' => (int) $row->refunded_count,
            'offline_count' => (int) $row->offline_count,
            'offline_amount' => (float) $row->offline_amount,
            'online_count' => (int) $row->online_count,
            'online_amount' => (float) $row->online_amount,
            'average_amount' => round((float) $row->average_amount, 2),
        ];
    }

    /**
     * Completed totals per campaign (general fund shown as its own row).
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array{campaign: string, count: int, amount: float}>
     */
    public function byCampaign(DateRange $range, array $filters = []): Collection
    {
        $rows = $this->base($range, $filters)->completed()
            ->selectRaw('donation_campaign_id, COUNT(*) as donations, COALESCE(SUM(amount), 0) as amount')
            ->groupBy('donation_campaign_id')
            ->orderByDesc('amount')
            ->get();

        $names = DonationCampaign::query()->whereIn('id', $rows->pluck('donation_campaign_id')->filter())->pluck('name', 'id');

        return $rows->map(fn ($row) => [
            'campaign' => $row->donation_campaign_id ? ($names[$row->donation_campaign_id] ?? 'Deleted campaign') : 'General fund',
            'count' => (int) $row->donations,
            'amount' => (float) $row->amount,
        ])->values();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, array{count: int, amount: float}>
     */
    public function byStatus(DateRange $range, array $filters = []): array
    {
        $rows = $this->base($range, $filters)
            ->selectRaw('payment_status, COUNT(*) as donations, COALESCE(SUM(amount), 0) as amount')
            ->groupBy('payment_status')
            ->get()
            ->keyBy('payment_status');

        $result = [];
        foreach (PaymentStatus::cases() as $status) {
            $row = $rows[$status->value] ?? null;
            $result[$status->label()] = ['count' => (int) ($row->donations ?? 0), 'amount' => (float) ($row->amount ?? 0)];
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, array{count: int, amount: float}>
     */
    public function byMethod(DateRange $range, array $filters = []): array
    {
        $rows = $this->base($range, $filters)->completed()
            ->selectRaw('payment_method, COUNT(*) as donations, COALESCE(SUM(amount), 0) as amount')
            ->groupBy('payment_method')
            ->get()
            ->keyBy('payment_method');

        $result = [];
        foreach (PaymentMethod::cases() as $method) {
            if (! isset($rows[$method->value])) {
                continue;
            }
            $result[$method->label()] = ['count' => (int) $rows[$method->value]->donations, 'amount' => (float) $rows[$method->value]->amount];
        }

        return $result;
    }

    /**
     * Rows for the aggregate CSV export (no donor data).
     *
     * @param  array<string, mixed>  $filters
     * @return iterable<list<string|int|float>>
     */
    public function exportRows(DateRange $range, array $filters = []): iterable
    {
        foreach ($this->series($this->base($range, $filters)->completed(), 'donated_at', $range, 'COALESCE(SUM(amount), 0)') as $period => $amount) {
            yield ['Period', $period, '', $amount];
        }

        foreach ($this->byCampaign($range, $filters) as $row) {
            yield ['Campaign', $row['campaign'], $row['count'], $row['amount']];
        }

        foreach ($this->byStatus($range, $filters) as $status => $row) {
            yield ['Status', $status, $row['count'], $row['amount']];
        }

        foreach ($this->byMethod($range, $filters) as $method => $row) {
            yield ['Payment method', $method, $row['count'], $row['amount']];
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function base(DateRange $range, array $filters): Builder
    {
        [$from, $to] = $range->bounds();

        return Donation::query()
            ->whereBetween('donated_at', [$from, $to])
            ->when(! empty($filters['campaign']), fn (Builder $q) => $q->where('donation_campaign_id', (int) $filters['campaign']))
            ->when(! empty($filters['status']), fn (Builder $q) => $q->where('payment_status', $filters['status']))
            ->when(! empty($filters['method']), fn (Builder $q) => $q->where('payment_method', $filters['method']));
    }
}
