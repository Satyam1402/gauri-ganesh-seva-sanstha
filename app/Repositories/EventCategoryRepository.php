<?php

namespace App\Repositories;

use App\Interfaces\EventCategoryRepositoryInterface;
use App\Models\EventCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class EventCategoryRepository extends BaseRepository implements EventCategoryRepositoryInterface
{
    public const CACHE_KEY = 'event-categories.active';

    public function __construct(EventCategory $model)
    {
        parent::__construct($model);
    }

    public function allOrdered(): Collection
    {
        return $this->model->withCount('events')
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
