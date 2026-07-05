<?php

namespace App\Repositories;

use App\Enums\EventStatus;
use App\Interfaces\EventRepositoryInterface;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class EventRepository extends BaseRepository implements EventRepositoryInterface
{
    public const CACHE_PREFIX = 'events';

    public function __construct(Event $model)
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

        $query->with(['category', 'media'])->withCount('registrations');

        if (! empty($filters['q'])) {
            $term = $filters['q'];
            $query->where(fn ($q) => $q->where('title', 'like', "%{$term}%")
                ->orWhere('venue', 'like', "%{$term}%")
                ->orWhere('city', 'like', "%{$term}%")
                ->orWhere('organizer', 'like', "%{$term}%"));
        }

        if (! empty($filters['category'])) {
            $query->where('event_category_id', $filters['category']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (($filters['when'] ?? null) === 'upcoming') {
            $query->upcoming();
        } elseif (($filters['when'] ?? null) === 'past') {
            $query->past();
        }

        if (array_key_exists('featured', $filters) && $filters['featured'] !== null && $filters['featured'] !== '') {
            $query->where('is_featured', (bool) $filters['featured']);
        }

        $sort = in_array($filters['sort'] ?? null, ['title', 'start_date', 'created_at', 'status'], true)
            ? $filters['sort']
            : 'start_date';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function publicPaginated(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        $cacheKey = self::CACHE_PREFIX.'.public.'.md5(serialize($filters)).'.page-'.request()->integer('page', 1);

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($filters, $perPage) {
            $query = $this->model->public()
                ->with(['category', 'media'])
                ->withCount(['activeRegistrations']);

            if (! empty($filters['q'])) {
                $term = $filters['q'];
                $query->where(fn ($q) => $q->where('title', 'like', "%{$term}%")
                    ->orWhere('short_description', 'like', "%{$term}%")
                    ->orWhere('venue', 'like', "%{$term}%")
                    ->orWhere('city', 'like', "%{$term}%"));
            }

            if (! empty($filters['category'])) {
                $query->whereHas('category', fn ($q) => $q->where('slug', $filters['category']));
            }

            // Past events list newest-first; upcoming ones soonest-first.
            if (($filters['when'] ?? 'upcoming') === 'past') {
                $query->past()->orderBy('start_date', 'desc');
            } else {
                $query->upcoming()->orderBy('start_date', 'asc');
            }

            return $query->paginate($perPage)->withQueryString();
        });
    }

    public function upcomingList(int $limit = 3): Collection
    {
        return Cache::remember(self::CACHE_PREFIX.".upcoming.{$limit}", now()->addMinutes(10), function () use ($limit) {
            return $this->model->published()->upcoming()
                ->with(['category', 'media'])
                ->withCount(['activeRegistrations'])
                ->orderBy('start_date', 'asc')
                ->limit($limit)
                ->get();
        });
    }

    public function featuredList(int $limit = 3): Collection
    {
        return Cache::remember(self::CACHE_PREFIX.".featured.{$limit}", now()->addMinutes(10), function () use ($limit) {
            return $this->model->published()->featured()->upcoming()
                ->with(['category', 'media'])
                ->withCount(['activeRegistrations'])
                ->orderBy('start_date', 'asc')
                ->limit($limit)
                ->get();
        });
    }

    public function related(Event $event, int $limit = 3): Collection
    {
        return $this->model->public()
            ->with(['category', 'media'])
            ->where('id', '!=', $event->id)
            ->where('event_category_id', $event->event_category_id)
            ->orderBy('start_date', 'desc')
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
            'published' => $this->model->published()->count(),
            'upcoming' => $this->model->published()->upcoming()->count(),
            'past' => $this->model->public()->past()->count(),
            'cancelled' => $this->model->where('status', EventStatus::Cancelled->value)->count(),
            'registrations' => EventRegistration::count(),
            'pending_registrations' => EventRegistration::where('status', 'pending')->count(),
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
}
