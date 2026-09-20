<?php

namespace App\Repositories;

use App\Enums\FaqStatus;
use App\Interfaces\FaqRepositoryInterface;
use App\Models\Faq;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class FaqRepository extends BaseRepository implements FaqRepositoryInterface
{
    public const CACHE_PREFIX = 'faqs';

    /**
     * Every public cache key embeds this version number; bumping it (see
     * FaqService::forgetCache) invalidates all public lists at once without
     * cache tags, which the file/database stores lack.
     */
    public const CACHE_VERSION_KEY = 'faqs.cache_version';

    /**
     * Upper bound for the dedicated page — FAQs are short and grouped by
     * category, so one page without pagination reads better than paging.
     */
    public const PUBLIC_LIMIT = 200;

    public function __construct(Faq $model)
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

        $query->with('category');

        if (! empty($filters['q'])) {
            $query->search($filters['q']);
        }

        if (! empty($filters['category'])) {
            $filters['category'] === 'none'
                ? $query->whereNull('faq_category_id')
                : $query->where('faq_category_id', $filters['category']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (array_key_exists('featured', $filters) && $filters['featured'] !== null && $filters['featured'] !== '') {
            $query->where('is_featured', (bool) $filters['featured']);
        }

        $sort = in_array($filters['sort'] ?? null, ['question', 'status', 'display_order', 'published_at', 'created_at'], true)
            ? $filters['sort']
            : 'created_at';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction)
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function publicList(array $filters): Collection
    {
        $term = trim((string) ($filters['q'] ?? ''));
        $category = (string) ($filters['category'] ?? '');

        $build = function () use ($term, $category) {
            $query = $this->model->published()->with('category')->ordered();

            if ($category !== '') {
                $query->whereHas('category', fn ($q) => $q->where('slug', $category));
            }

            if ($term !== '') {
                $query->search($term);
            }

            return $query->limit(self::PUBLIC_LIMIT)->get();
        };

        // Free-text searches are unbounded input — never cache them.
        if ($term !== '') {
            return $build();
        }

        return Cache::remember($this->key('public.'.md5($category)), now()->addMinutes(10), $build);
    }

    public function sectionList(?string $categorySlug = null, bool $featuredOnly = false, int $limit = 6): Collection
    {
        $slug = (string) $categorySlug;
        $flag = $featuredOnly ? 'featured' : 'all';

        return Cache::remember($this->key('section.'.md5($slug).".{$flag}.{$limit}"), now()->addMinutes(10), function () use ($slug, $featuredOnly, $limit) {
            $query = $this->model->published()->with('category')->ordered();

            if ($slug !== '') {
                $query->whereHas('category', fn ($q) => $q->where('slug', $slug));
            }

            if ($featuredOnly) {
                $query->featured();
            }

            return $query->limit($limit)->get();
        });
    }

    /**
     * @return array<string, int>
     */
    public function statistics(): array
    {
        return [
            'total' => $this->model->count(),
            'published' => $this->model->where('status', FaqStatus::Published->value)->count(),
            'draft' => $this->model->where('status', FaqStatus::Draft->value)->count(),
            'featured' => $this->model->where('status', FaqStatus::Published->value)->featured()->count(),
            'uncategorised' => $this->model->whereNull('faq_category_id')->count(),
            'archived' => $this->model->where('status', FaqStatus::Archived->value)->count(),
        ];
    }

    public function bulkDelete(array $ids): int
    {
        return $this->model->whereIn('id', $ids)->delete();
    }

    public function bulkUpdateStatus(array $ids, string $status): int
    {
        return $this->model->whereIn('id', $ids)->update(['status' => $status]);
    }

    /**
     * Versioned cache key — see CACHE_VERSION_KEY.
     */
    private function key(string $suffix): string
    {
        $version = (int) Cache::get(self::CACHE_VERSION_KEY, 1);

        return self::CACHE_PREFIX.".v{$version}.{$suffix}";
    }
}
