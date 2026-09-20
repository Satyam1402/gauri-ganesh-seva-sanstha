<?php

namespace App\Repositories;

use App\Enums\TestimonialStatus;
use App\Enums\TestimonialType;
use App\Interfaces\TestimonialRepositoryInterface;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class TestimonialRepository extends BaseRepository implements TestimonialRepositoryInterface
{
    public const CACHE_PREFIX = 'testimonials';

    /**
     * Every public cache key embeds this version number; bumping it (see
     * TestimonialService::forgetCache) invalidates all public lists at once
     * without needing cache tags, which the file/database stores lack.
     */
    public const CACHE_VERSION_KEY = 'testimonials.cache_version';

    public function __construct(Testimonial $model)
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

        $query->with(['media', 'testimonialable']);

        if (! empty($filters['q'])) {
            $term = $filters['q'];
            $query->where(fn ($q) => $q->where('name', 'like', "%{$term}%")
                ->orWhere('designation', 'like', "%{$term}%")
                ->orWhere('organization', 'like', "%{$term}%")
                ->orWhere('location', 'like', "%{$term}%")
                ->orWhere('content', 'like', "%{$term}%"));
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (array_key_exists('featured', $filters) && $filters['featured'] !== null && $filters['featured'] !== '') {
            $query->where('is_featured', (bool) $filters['featured']);
        }

        if (array_key_exists('consent', $filters) && $filters['consent'] !== null && $filters['consent'] !== '') {
            $query->where('consent_given', (bool) $filters['consent']);
        }

        $sort = in_array($filters['sort'] ?? null, ['name', 'type', 'status', 'rating', 'display_order', 'published_at', 'created_at'], true)
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
    public function publicPaginated(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        $type = $this->validType($filters['type'] ?? null);
        $page = request()->integer('page', 1);

        return Cache::remember($this->key("public.{$type}.page-{$page}.{$perPage}"), now()->addMinutes(10), function () use ($type, $perPage) {
            $query = $this->model->published()->with('media')->ordered();

            if ($type !== '') {
                $query->ofType($type);
            }

            return $query->paginate($perPage)->withQueryString();
        });
    }

    public function sectionList(?string $type = null, bool $featuredOnly = false, int $limit = 3): Collection
    {
        $type = $this->validType($type);
        $flag = $featuredOnly ? 'featured' : 'all';

        return Cache::remember($this->key("section.{$type}.{$flag}.{$limit}"), now()->addMinutes(10), function () use ($type, $featuredOnly, $limit) {
            $query = $this->model->published()->with('media')->ordered();

            if ($type !== '') {
                $query->ofType($type);
            }

            if ($featuredOnly) {
                $query->featured();
            }

            return $query->limit($limit)->get();
        });
    }

    public function forRelated(Model $related, int $limit = 3): Collection
    {
        $key = $this->key('related.'.md5($related->getMorphClass()).".{$related->getKey()}.{$limit}");

        return Cache::remember($key, now()->addMinutes(10), function () use ($related, $limit) {
            return $this->model->published()
                ->with('media')
                ->where('testimonialable_type', $related->getMorphClass())
                ->where('testimonialable_id', $related->getKey())
                ->ordered()
                ->limit($limit)
                ->get();
        });
    }

    /**
     * @return array<string, int>
     */
    public function publicTypeCounts(): array
    {
        return Cache::remember($this->key('type-counts'), now()->addMinutes(10), function () {
            $counts = $this->model->published()
                ->selectRaw('type, COUNT(*) as aggregate')
                ->groupBy('type')
                ->pluck('aggregate', 'type')
                ->all();

            return array_map(fn (int|string $n) => (int) $n, $counts);
        });
    }

    /**
     * @return array<string, int>
     */
    public function statistics(): array
    {
        return [
            'total' => $this->model->count(),
            'published' => $this->model->published()->count(),
            'pending_review' => $this->model->where('status', TestimonialStatus::PendingReview->value)->count(),
            'featured' => $this->model->published()->featured()->count(),
            'without_consent' => $this->model->where('consent_given', false)->count(),
            'archived' => $this->model->where('status', TestimonialStatus::Archived->value)->count(),
        ];
    }

    public function bulkDelete(array $ids): int
    {
        return $this->model->whereIn('id', $ids)->delete();
    }

    public function bulkUpdateStatus(array $ids, string $status, array $extra = []): int
    {
        return $this->model->whereIn('id', $ids)->update(['status' => $status] + $extra);
    }

    /**
     * Versioned cache key — see CACHE_VERSION_KEY.
     */
    private function key(string $suffix): string
    {
        $version = (int) Cache::get(self::CACHE_VERSION_KEY, 1);

        return self::CACHE_PREFIX.".v{$version}.{$suffix}";
    }

    /**
     * Unknown type values collapse to "all" so arbitrary query strings can't
     * fan out into unbounded cache keys.
     */
    private function validType(?string $type): string
    {
        return in_array($type, TestimonialType::values(), true) ? $type : '';
    }
}
