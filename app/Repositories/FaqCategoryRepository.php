<?php

namespace App\Repositories;

use App\Interfaces\FaqCategoryRepositoryInterface;
use App\Models\FaqCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FaqCategoryRepository extends BaseRepository implements FaqCategoryRepositoryInterface
{
    public const CACHE_KEY = 'faq-categories.active';

    public function __construct(FaqCategory $model)
    {
        parent::__construct($model);
    }

    public function allOrdered(): Collection
    {
        return $this->model->withCount('faqs')
            ->ordered()
            ->get();
    }

    public function activeOrdered(): Collection
    {
        return Cache::remember(self::CACHE_KEY, now()->addHour(), function () {
            return $this->model->active()
                ->withCount(['faqs' => fn ($q) => $q->published()])
                ->ordered()
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
