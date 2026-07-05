<?php

namespace App\Repositories;

use App\Enums\PaymentStatus;
use App\Interfaces\DonationRepositoryInterface;
use App\Models\Donation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\LazyCollection;

class DonationRepository extends BaseRepository implements DonationRepositoryInterface
{
    public function __construct(Donation $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function adminSearch(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->filteredQuery($filters)->with('campaign');

        $sort = in_array($filters['sort'] ?? null, ['donated_at', 'amount', 'donor_name', 'payment_status', 'created_at'], true)
            ? $filters['sort']
            : 'donated_at';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function exportCursor(array $filters): LazyCollection
    {
        return $this->filteredQuery($filters)
            ->with('campaign')
            ->orderBy('donated_at', 'desc')
            ->cursor();
    }

    public function revenueSummary(): array
    {
        $completed = $this->model->completed();

        $total = (float) (clone $completed)->sum('amount');
        $count = (clone $completed)->count();

        return [
            'total' => $total,
            'count' => $count,
            'average' => $count > 0 ? round($total / $count, 2) : 0.0,
            'this_month' => (float) (clone $completed)
                ->whereBetween('donated_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('amount'),
            'this_year' => (float) (clone $completed)
                ->whereBetween('donated_at', [now()->startOfYear(), now()->endOfYear()])
                ->sum('amount'),
            'pending_count' => $this->model
                ->where('payment_status', PaymentStatus::Pending->value)
                ->count(),
        ];
    }

    public function monthlyTotals(int $months = 12): array
    {
        $start = now()->startOfMonth()->subMonths($months - 1);

        // Grouped in PHP rather than with DATE_FORMAT/strftime so the same
        // code runs on MySQL in production and SQLite in the test suite.
        // Donation volume per year is small enough that this stays cheap.
        $rows = $this->model->completed()
            ->where('donated_at', '>=', $start)
            ->get(['donated_at', 'amount'])
            ->groupBy(fn (Donation $donation) => $donation->donated_at->format('Y-m'));

        $totals = [];

        for ($i = 0; $i < $months; $i++) {
            $month = $start->copy()->addMonths($i);
            $bucket = $rows->get($month->format('Y-m'), collect());

            $totals[] = [
                'month' => $month->format('M Y'),
                'total' => (float) $bucket->sum('amount'),
                'count' => $bucket->count(),
            ];
        }

        return $totals;
    }

    public function topDonors(int $limit = 10): BaseCollection
    {
        return $this->model->completed()
            ->where('is_anonymous', false)
            ->selectRaw('donor_email, MAX(donor_name) as donor_name, SUM(amount) as total_amount, COUNT(*) as donations_count, MAX(donated_at) as last_donated_at')
            ->groupBy('donor_email')
            ->orderByDesc('total_amount')
            ->limit($limit)
            ->get();
    }

    public function recentCompleted(int $limit = 10): Collection
    {
        return $this->model->completed()
            ->with('campaign')
            ->orderBy('donated_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function filteredQuery(array $filters): Builder
    {
        $query = ! empty($filters['trashed'])
            ? $this->model->onlyTrashed()
            : $this->model->newQuery();

        if (! empty($filters['q'])) {
            $term = $filters['q'];
            $query->where(fn ($q) => $q->where('donor_name', 'like', "%{$term}%")
                ->orWhere('donor_email', 'like', "%{$term}%")
                ->orWhere('donor_phone', 'like', "%{$term}%")
                ->orWhere('receipt_number', 'like', "%{$term}%")
                ->orWhere('transaction_id', 'like', "%{$term}%"));
        }

        if (! empty($filters['campaign'])) {
            $query->where('donation_campaign_id', $filters['campaign']);
        }

        if (! empty($filters['status'])) {
            $query->where('payment_status', $filters['status']);
        }

        if (! empty($filters['method'])) {
            $query->where('payment_method', $filters['method']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('donated_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('donated_at', '<=', $filters['date_to']);
        }

        return $query;
    }
}
