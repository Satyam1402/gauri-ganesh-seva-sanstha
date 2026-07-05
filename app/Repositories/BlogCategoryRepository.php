<?php

namespace App\Repositories;

use App\Interfaces\BlogCategoryRepositoryInterface;
use App\Models\BlogCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BlogCategoryRepository extends BaseRepository implements BlogCategoryRepositoryInterface
{
    public const CACHE_KEY = 'blog-categories.active';

    public function __construct(BlogCategory $model)
    {
        parent::__construct($model);
    }

    public function allOrdered(): Collection
    {
        return $this->model->withCount('posts')
            ->orderBy('order_column')
            ->get();
    }

    public function activeOrdered(): Collection
    {
        return Cache::remember(self::CACHE_KEY, now()->addHour(), function () {
            return $this->model->query()
                ->where('is_active', true)
                ->withCount(['posts' => fn ($q) => $q->published()])
                ->orderBy('order_column')
                ->get();
        });
    }

    public function reorder(array $orderedIds): void
    {
        DB::transaction(function () use ($orderedIds) {
            foreach ($orderedIds as $order => $id) {
                $this->model->whereKey($id)->update(['order_column' => $order]);
            }
        });
    }
}
