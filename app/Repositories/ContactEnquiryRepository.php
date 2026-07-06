<?php

namespace App\Repositories;

use App\Enums\EnquiryStatus;
use App\Interfaces\ContactEnquiryRepositoryInterface;
use App\Models\ContactEnquiry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\LazyCollection;

class ContactEnquiryRepository extends BaseRepository implements ContactEnquiryRepositoryInterface
{
    public function __construct(ContactEnquiry $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function adminSearch(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->filtered($filters)
            ->with(['assignee:id,name', 'media'])
            ->withCount('replies')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function exportCursor(array $filters): LazyCollection
    {
        return $this->filtered($filters)
            ->with('assignee:id,name')
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
     * @return Collection<int, ContactEnquiry>
     */
    public function findMany(array $ids): Collection
    {
        return $this->model->newQuery()->whereIn('id', $ids)->get();
    }

    /**
     * Status changes carry no email side effects for enquiries, so bulk
     * updates run as a single UPDATE instead of a per-model loop.
     *
     * @param  list<int>  $ids
     */
    public function bulkUpdateStatus(array $ids, EnquiryStatus $status): int
    {
        return $this->model->newQuery()
            ->whereIn('id', $ids)
            ->update(['status' => $status->value]);
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
                ->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('subject', 'like', "%{$term}%"));
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['assigned'])) {
            $query->where('assigned_to', $filters['assigned']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        return match ($filters['sort'] ?? null) {
            'oldest' => $query->orderBy('created_at'),
            'name' => $query->orderBy('name'),
            default => $query->orderBy('created_at', 'desc'),
        };
    }
}
