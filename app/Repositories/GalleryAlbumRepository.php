<?php

namespace App\Repositories;

use App\Interfaces\GalleryAlbumRepositoryInterface;
use App\Models\GalleryAlbum;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class GalleryAlbumRepository extends BaseRepository implements GalleryAlbumRepositoryInterface
{
    public const CACHE_PREFIX = 'gallery_albums';

    public function __construct(GalleryAlbum $model)
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

        $query->with(['category', 'media'])->withCount(['photos', 'videos']);

        if (! empty($filters['q'])) {
            $term = $filters['q'];
            $query->where(fn ($q) => $q->where('title', 'like', "%{$term}%")
                ->orWhere('location', 'like', "%{$term}%"));
        }

        if (! empty($filters['category'])) {
            $query->where('gallery_category_id', $filters['category']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (array_key_exists('featured', $filters) && $filters['featured'] !== null && $filters['featured'] !== '') {
            $query->where('is_featured', (bool) $filters['featured']);
        }

        $sort = in_array($filters['sort'] ?? null, ['title', 'event_date', 'created_at', 'status', 'order_column'], true)
            ? $filters['sort']
            : 'event_date';
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
            $query = $this->model->published()
                ->with(['category', 'media'])
                ->withCount('activePhotos');

            if (! empty($filters['q'])) {
                $term = $filters['q'];
                $query->where(fn ($q) => $q->where('title', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('location', 'like', "%{$term}%"));
            }

            if (! empty($filters['category'])) {
                $query->whereHas('category', fn ($q) => $q->where('slug', $filters['category']));
            }

            match ($filters['sort'] ?? null) {
                'oldest' => $query->orderBy('event_date', 'asc'),
                'title' => $query->orderBy('title', 'asc'),
                default => $query->ordered(),
            };

            return $query->paginate($perPage)->withQueryString();
        });
    }

    public function latest(int $limit = 6): Collection
    {
        return Cache::remember(self::CACHE_PREFIX.".latest.{$limit}", now()->addMinutes(10), function () use ($limit) {
            return $this->model->published()
                ->with(['category', 'media'])
                ->withCount('activePhotos')
                ->orderBy('event_date', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    public function featuredList(int $limit = 3): Collection
    {
        return Cache::remember(self::CACHE_PREFIX.".featured.{$limit}", now()->addMinutes(10), function () use ($limit) {
            return $this->model->published()->featured()
                ->with(['category', 'media'])
                ->withCount('activePhotos')
                ->ordered()
                ->limit($limit)
                ->get();
        });
    }

    public function related(GalleryAlbum $album, int $limit = 3): Collection
    {
        return $this->model->published()
            ->with(['category', 'media'])
            ->withCount('activePhotos')
            ->where('id', '!=', $album->id)
            ->where('gallery_category_id', $album->gallery_category_id)
            ->orderBy('event_date', 'desc')
            ->limit($limit)
            ->get();
    }
}
