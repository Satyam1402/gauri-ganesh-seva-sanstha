<?php

namespace App\Repositories;

use App\Interfaces\EventRegistrationRepositoryInterface;
use App\Models\EventRegistration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\LazyCollection;

class EventRegistrationRepository extends BaseRepository implements EventRegistrationRepositoryInterface
{
    public function __construct(EventRegistration $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function adminSearch(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->filtered($filters)
            ->with('event:id,title,slug,start_date')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function exportCursor(array $filters): LazyCollection
    {
        return $this->filtered($filters)
            ->with('event:id,title,slug,start_date')
            ->orderBy('created_at', 'desc')
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
     * @param  array<string, mixed>  $filters
     */
    private function filtered(array $filters): Builder
    {
        $query = $this->model->newQuery();

        if (! empty($filters['q'])) {
            $term = $filters['q'];
            $query->where(fn ($q) => $q->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('city', 'like', "%{$term}%"));
        }

        if (! empty($filters['event'])) {
            $query->where('event_id', $filters['event']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        return $query;
    }
}
