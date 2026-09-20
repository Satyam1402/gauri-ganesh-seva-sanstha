<?php

namespace App\Repositories;

use App\Enums\PartnerStatus;
use App\Interfaces\PartnerRepositoryInterface;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class PartnerRepository extends BaseRepository implements PartnerRepositoryInterface
{
    public const CACHE_PREFIX = 'partners';

    /**
     * Every public cache key embeds this version number; bumping it (see
     * PartnerService::forgetCache) invalidates all public lists at once
     * without cache tags, which the file/database stores lack.
     */
    public const CACHE_VERSION_KEY = 'partners.cache_version';

    /**
     * Upper bound for the dedicated page — a logo wall reads better as one
     * grouped page than paginated.
     */
    public const PUBLIC_LIMIT = 200;

    public function __construct(Partner $model)
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

        $query->with(['type', 'media']);

        if (! empty($filters['q'])) {
            $term = $filters['q'];
            $query->where(fn ($q) => $q->where('name', 'like', "%{$term}%")
                ->orWhere('short_description', 'like', "%{$term}%")
                ->orWhere('website_url', 'like', "%{$term}%")
                ->orWhere('city', 'like', "%{$term}%"));
        }

        if (! empty($filters['type'])) {
            $filters['type'] === 'none'
                ? $query->whereNull('partner_type_id')
                : $query->where('partner_type_id', $filters['type']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (array_key_exists('featured', $filters) && $filters['featured'] !== null && $filters['featured'] !== '') {
            $query->where('is_featured', (bool) $filters['featured']);
        }

        $sort = in_array($filters['sort'] ?? null, ['name', 'status', 'display_order', 'started_on', 'created_at'], true)
            ? $filters['sort']
            : 'created_at';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction)
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function publicList(?string $typeSlug = null): Collection
    {
        $slug = (string) $typeSlug;

        return Cache::remember($this->key('public.'.md5($slug)), now()->addMinutes(10), function () use ($slug) {
            $query = $this->model->active()->with(['type', 'media'])->ordered();

            if ($slug !== '') {
                $query->whereHas('type', fn ($q) => $q->where('slug', $slug));
            }

            return $query->limit(self::PUBLIC_LIMIT)->get();
        });
    }

    public function sectionList(?string $typeSlug = null, bool $featuredOnly = false, int $limit = 8): Collection
    {
        $slug = (string) $typeSlug;
        $flag = $featuredOnly ? 'featured' : 'all';

        return Cache::remember($this->key('section.'.md5($slug).".{$flag}.{$limit}"), now()->addMinutes(10), function () use ($slug, $featuredOnly, $limit) {
            $query = $this->model->active()->with(['type', 'media'])->ordered();

            if ($slug !== '') {
                $query->whereHas('type', fn ($q) => $q->where('slug', $slug));
            }

            if ($featuredOnly) {
                $query->featured();
            }

            return $query->limit($limit)->get();
        });
    }

    public function related(Partner $partner, int $limit = 4): Collection
    {
        return $this->model->active()
            ->with(['type', 'media'])
            ->where('id', '!=', $partner->id)
            ->where('partner_type_id', $partner->partner_type_id)
            ->ordered()
            ->limit($limit)
            ->get();
    }

    /**
     * @return array<string, int>
     */
    public function statistics(): array
    {
        return [
            'total' => $this->model->count(),
            'active' => $this->model->where('status', PartnerStatus::Active->value)->count(),
            'inactive' => $this->model->where('status', PartnerStatus::Inactive->value)->count(),
            'draft' => $this->model->where('status', PartnerStatus::Draft->value)->count(),
            'featured' => $this->model->where('status', PartnerStatus::Active->value)->featured()->count(),
            'archived' => $this->model->where('status', PartnerStatus::Archived->value)->count(),
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
