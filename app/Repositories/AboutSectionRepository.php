<?php

namespace App\Repositories;

use App\Interfaces\AboutSectionRepositoryInterface;
use App\Models\AboutSection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AboutSectionRepository extends BaseRepository implements AboutSectionRepositoryInterface
{
    public const CACHE_KEY = 'about.sections';

    public function __construct(AboutSection $model)
    {
        parent::__construct($model);
    }

    public function allOrdered(): Collection
    {
        return $this->model->with(['buttons', 'items', 'media'])
            ->orderBy('order_column')
            ->get();
    }

    public function activeForAboutPage(): Collection
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
