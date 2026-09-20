<?php

namespace App\Repositories;

use App\Enums\MenuLocation;
use App\Interfaces\MenuItemRepositoryInterface;
use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MenuItemRepository extends BaseRepository implements MenuItemRepositoryInterface
{
    public const CACHE_PREFIX = 'menus';

    public function __construct(MenuItem $model)
    {
        parent::__construct($model);
    }

    public static function cacheKey(MenuLocation $location): string
    {
        return self::CACHE_PREFIX.'.'.$location->value;
    }

    public function tree(MenuLocation $location): Collection
    {
        return Cache::rememberForever(self::cacheKey($location), function () use ($location) {
            return $this->model->forLocation($location)
                ->active()
                ->topLevel()
                ->with(['children' => fn ($q) => $q->active()])
                ->orderBy('order_column')
                ->get();
        });
    }

    public function allForLocation(MenuLocation $location): Collection
    {
        return $this->model->forLocation($location)
            ->topLevel()
            ->with('children')
            ->orderBy('order_column')
            ->get();
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
