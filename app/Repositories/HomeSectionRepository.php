<?php

namespace App\Repositories;

use App\Interfaces\HomeSectionRepositoryInterface;
use App\Models\HomeSection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HomeSectionRepository extends BaseRepository implements HomeSectionRepositoryInterface
{
    public const CACHE_KEY = 'homepage.sections';

    public function __construct(HomeSection $model)
    {
        parent::__construct($model);
    }

    public function allOrdered(): Collection
    {
        return $this->model->with(['buttons', 'items', 'media'])
            ->orderBy('order_column')
            ->get();
    }

    public function activeForHomepage(): Collection
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return $this->model->query()
                ->where('is_active', true)
                ->orderBy('order_column')
                ->with([
                    'buttons',
                    'activeItems.media',
                    'media',
                ])
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
