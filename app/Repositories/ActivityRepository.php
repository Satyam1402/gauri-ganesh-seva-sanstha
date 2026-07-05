<?php

namespace App\Repositories;

use App\Interfaces\ActivityRepositoryInterface;
use App\Models\Activity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class ActivityRepository extends BaseRepository implements ActivityRepositoryInterface
{
    public const CACHE_PREFIX = 'activities';

    public function __construct(Activity $model)
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

        $query->with(['category', 'media']);

        if (! empty($filters['q'])) {
            $term = $filters['q'];
            $query->where(fn ($q) => $q->where('title', 'like', "%{$term}%")
                ->orWhere('location', 'like', "%{$term}%")
                ->orWhere('organizer', 'like', "%{$term}%"));
        }

        if (! empty($filters['category'])) {
            $query->where('activity_category_id', $filters['category']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (array_key_exists('featured', $filters) && $filters['featured'] !== null && $filters['featured'] !== '') {
            $query->where('is_featured', (bool) $filters['featured']);
        }

        $sort = in_array($filters['sort'] ?? null, ['title', 'activity_date', 'created_at', 'status'], true)
            ? $filters['sort']
            : 'activity_date';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function publishedPaginated(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        $cacheKey = self::CACHE_PREFIX.'.public.'.md5(serialize($filters)).'.page-'.request()->integer('page', 1);

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($filters, $perPage) {
            $query = $this->model->published()->with(['category', 'media']);

            if (! empty($filters['q'])) {
                $term = $filters['q'];
                $query->where(fn ($q) => $q->where('title', 'like', "%{$term}%")
                    ->orWhere('short_description', 'like', "%{$term}%"));
            }

            if (! empty($filters['category'])) {
                $query->whereHas('category', fn ($q) => $q->where('slug', $filters['category']));
            }

            $sort = $filters['sort'] ?? 'latest';
            match ($sort) {
                'oldest' => $query->orderBy('activity_date', 'asc'),
                'title' => $query->orderBy('title', 'asc'),
                default => $query->orderBy('activity_date', 'desc'),
            };

            return $query->paginate($perPage)->withQueryString();
        });
    }

    public function latest(int $limit = 6): Collection
    {
        return Cache::remember(self::CACHE_PREFIX.".latest.{$limit}", now()->addMinutes(10), function () use ($limit) {
            return $this->model->published()
                ->with(['category', 'media'])
                ->orderBy('activity_date', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    public function featuredList(int $limit = 6): Collection
    {
        return Cache::remember(self::CACHE_PREFIX.".featured.{$limit}", now()->addMinutes(10), function () use ($limit) {
            return $this->model->published()->featured()
                ->with(['category', 'media'])
                ->orderBy('activity_date', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    public function related(Activity $activity, int $limit = 3): Collection
    {
        return $this->model->published()
            ->with(['category', 'media'])
            ->where('id', '!=', $activity->id)
            ->where('activity_category_id', $activity->activity_category_id)
            ->orderBy('activity_date', 'desc')
            ->limit($limit)
            ->get();
    }

    public function bulkDelete(array $ids): int
    {
        return $this->model->whereIn('id', $ids)->delete();
    }

    public function bulkUpdateStatus(array $ids, string $status): int
    {
        return $this->model->whereIn('id', $ids)->update(['status' => $status]);
    }

    public function bulkUpdateCategory(array $ids, int $categoryId): int
    {
        return $this->model->whereIn('id', $ids)->update(['activity_category_id' => $categoryId]);
    }
}
