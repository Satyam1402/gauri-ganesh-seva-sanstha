<?php

namespace App\Repositories;

use App\Interfaces\BlogCommentRepositoryInterface;
use App\Models\BlogComment;
use Illuminate\Pagination\LengthAwarePaginator;

class BlogCommentRepository extends BaseRepository implements BlogCommentRepositoryInterface
{
    public function __construct(BlogComment $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function adminSearch(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->with('post:id,title,slug');

        if (! empty($filters['q'])) {
            $term = $filters['q'];
            $query->where(fn ($q) => $q->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('body', 'like', "%{$term}%"));
        }

        if (! empty($filters['post'])) {
            $query->where('blog_post_id', $filters['post']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
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
}
