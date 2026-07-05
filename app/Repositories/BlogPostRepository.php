<?php

namespace App\Repositories;

use App\Interfaces\BlogPostRepositoryInterface;
use App\Models\BlogPost;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class BlogPostRepository extends BaseRepository implements BlogPostRepositoryInterface
{
    public const CACHE_PREFIX = 'blog';

    public function __construct(BlogPost $model)
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

        $query->with(['category', 'author', 'media'])->withCount(['comments', 'approvedComments']);

        if (! empty($filters['q'])) {
            $term = $filters['q'];
            $query->where(fn ($q) => $q->where('title', 'like', "%{$term}%")
                ->orWhere('excerpt', 'like', "%{$term}%"));
        }

        if (! empty($filters['category'])) {
            $query->where('blog_category_id', $filters['category']);
        }

        if (! empty($filters['status'])) {
            // "scheduled" is a virtual status: published but not yet live.
            if ($filters['status'] === 'scheduled') {
                $query->where('status', 'published')->where('published_at', '>', now());
            } else {
                $query->where('status', $filters['status']);
            }
        }

        if (array_key_exists('featured', $filters) && $filters['featured'] !== null && $filters['featured'] !== '') {
            $query->where('is_featured', (bool) $filters['featured']);
        }

        $sort = in_array($filters['sort'] ?? null, ['title', 'published_at', 'created_at', 'views_count', 'status'], true)
            ? $filters['sort']
            : 'published_at';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function publishedPaginated(array $filters, int $perPage = 9): LengthAwarePaginator
    {
        $cacheKey = self::CACHE_PREFIX.'.public.'.md5(serialize($filters)).'.page-'.request()->integer('page', 1);

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($filters, $perPage) {
            $query = $this->model->published()
                ->with(['category', 'author', 'media', 'tags']);

            if (! empty($filters['q'])) {
                $term = $filters['q'];
                $query->where(fn ($q) => $q->where('title', 'like', "%{$term}%")
                    ->orWhere('excerpt', 'like', "%{$term}%")
                    ->orWhere('content', 'like', "%{$term}%"));
            }

            if (! empty($filters['category'])) {
                $query->whereHas('category', fn ($q) => $q->where('slug', $filters['category']));
            }

            if (! empty($filters['tag'])) {
                $query->whereHas('tags', fn ($q) => $q->where('slug', $filters['tag']));
            }

            return $query->orderBy('published_at', 'desc')
                ->paginate($perPage)
                ->withQueryString();
        });
    }

    public function latest(int $limit = 3): Collection
    {
        return Cache::remember(self::CACHE_PREFIX.".latest.{$limit}", now()->addMinutes(10), function () use ($limit) {
            return $this->model->published()
                ->with(['category', 'author', 'media'])
                ->orderBy('published_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    public function featuredList(int $limit = 3): Collection
    {
        return Cache::remember(self::CACHE_PREFIX.".featured.{$limit}", now()->addMinutes(10), function () use ($limit) {
            return $this->model->published()->featured()
                ->with(['category', 'author', 'media'])
                ->orderBy('published_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    public function popular(int $limit = 5): Collection
    {
        return Cache::remember(self::CACHE_PREFIX.".popular.{$limit}", now()->addMinutes(10), function () use ($limit) {
            return $this->model->published()
                ->with(['category', 'media'])
                ->orderBy('views_count', 'desc')
                ->orderBy('published_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    public function related(BlogPost $post, int $limit = 3): Collection
    {
        return $this->model->published()
            ->with(['category', 'author', 'media'])
            ->where('id', '!=', $post->id)
            ->where('blog_category_id', $post->blog_category_id)
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function incrementViews(BlogPost $post): void
    {
        // Bypasses fillable/timestamps — a view is not an edit.
        $this->model->whereKey($post->id)->increment('views_count');
    }

    public function bulkDelete(array $ids): int
    {
        return $this->model->whereIn('id', $ids)->delete();
    }

    public function bulkUpdateStatus(array $ids, string $status): int
    {
        return $this->model->whereIn('id', $ids)->update(['status' => $status]);
    }
}
