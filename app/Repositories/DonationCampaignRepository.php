<?php

namespace App\Repositories;

use App\Interfaces\DonationCampaignRepositoryInterface;
use App\Models\DonationCampaign;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DonationCampaignRepository extends BaseRepository implements DonationCampaignRepositoryInterface
{
    public const CACHE_PREFIX = 'donation_campaigns';

    public function __construct(DonationCampaign $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function adminSearch(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = ! empty($filters['trashed'])
            ? $this->model->onlyTrashed()
            : $this->model->newQuery();

        $query->with('media')->withCount('donations');

        if (! empty($filters['q'])) {
            $term = $filters['q'];
            $query->where(fn ($q) => $q->where('name', 'like', "%{$term}%")
                ->orWhere('short_description', 'like', "%{$term}%"));
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (array_key_exists('featured', $filters) && $filters['featured'] !== null && $filters['featured'] !== '') {
            $query->where('is_featured', (bool) $filters['featured']);
        }

        $sort = in_array($filters['sort'] ?? null, ['name', 'goal_amount', 'raised_amount', 'created_at', 'status', 'order_column'], true)
            ? $filters['sort']
            : 'order_column';
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return $query->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function allOrdered(): Collection
    {
        return $this->model->ordered()->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function activePaginated(array $filters, int $perPage = 9): LengthAwarePaginator
    {
        $cacheKey = self::CACHE_PREFIX.'.public.'.md5(serialize($filters)).'.page-'.request()->integer('page', 1);

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($filters, $perPage) {
            $query = $this->model->active()->with('media');

            if (! empty($filters['q'])) {
                $term = $filters['q'];
                $query->where(fn ($q) => $q->where('name', 'like', "%{$term}%")
                    ->orWhere('short_description', 'like', "%{$term}%"));
            }

            match ($filters['sort'] ?? null) {
                'goal' => $query->orderByRaw('goal_amount IS NULL')->orderBy('goal_amount', 'desc'),
                'newest' => $query->orderBy('created_at', 'desc'),
                default => $query->orderBy('is_featured', 'desc')->orderBy('order_column'),
            };

            return $query->paginate($perPage)->withQueryString();
        });
    }

    public function activeOrdered(): Collection
    {
        return $this->model->active()->ordered()->get();
    }

    public function featuredList(int $limit = 3): Collection
    {
        return Cache::remember(self::CACHE_PREFIX.".featured.{$limit}", now()->addMinutes(10), function () use ($limit) {
            return $this->model->active()->featured()
                ->with('media')
                ->ordered()
                ->limit($limit)
                ->get();
        });
    }

    public function reorder(array $orderedIds): void
    {
        DB::transaction(function () use ($orderedIds): void {
            foreach (array_values($orderedIds) as $position => $id) {
                $this->model->whereKey($id)->update(['order_column' => $position + 1]);
            }
        });
    }
}
