<?php

namespace App\Repositories;

use App\Interfaces\VolunteerApplicationRepositoryInterface;
use App\Models\VolunteerApplication;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\LazyCollection;

class VolunteerApplicationRepository extends BaseRepository implements VolunteerApplicationRepositoryInterface
{
    public function __construct(VolunteerApplication $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function adminSearch(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->filtered($filters)
            ->with(['reviewer:id,name', 'media'])
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function exportCursor(array $filters): LazyCollection
    {
        return $this->filtered($filters)
            ->with('reviewer:id,name')
            ->cursor();
    }

    /**
     * @return array<string, int>
     */
    public function countsByStatus(): array
    {
        return $this->model->query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, VolunteerApplication>
     */
    public function findMany(array $ids): Collection
    {
        return $this->model->newQuery()->whereIn('id', $ids)->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function filtered(array $filters): Builder
    {
        $query = $this->model->newQuery();

        if (! empty($filters['trashed'])) {
            $query->onlyTrashed();
        }

        if (! empty($filters['q'])) {
            $term = $filters['q'];
            $query->where(fn ($q) => $q
                ->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$term}%"])
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('city', 'like', "%{$term}%"));
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['availability'])) {
            $query->where('availability', $filters['availability']);
        }

        if (! empty($filters['interest'])) {
            $query->whereJsonContains('areas_of_interest', $filters['interest']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        return match ($filters['sort'] ?? null) {
            'oldest' => $query->orderBy('created_at'),
            'name' => $query->orderBy('first_name')->orderBy('last_name'),
            default => $query->orderBy('created_at', 'desc'),
        };
    }
}
