<?php

namespace App\Repositories;

use App\Interfaces\ActivityCategoryRepositoryInterface;
use App\Models\ActivityCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ActivityCategoryRepository extends BaseRepository implements ActivityCategoryRepositoryInterface
{
    public const CACHE_KEY = 'activity-categories.active';

    public function __construct(ActivityCategory $model)
    {
        parent::__construct($model);
    }

    public function allOrdered(): Collection
    {
        return $this->model->withCount('activities')
            ->orderBy('order_column')
            ->get();
    }

    public function activeOrdered(): Collection
    {
        return Cache::remember(self::CACHE_KEY, now()->addHour(), function () {
            return $this->model->query()
                ->where('is_active', true)
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
