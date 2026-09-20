<?php

namespace App\Repositories;

use App\Interfaces\PartnerTypeRepositoryInterface;
use App\Models\PartnerType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PartnerTypeRepository extends BaseRepository implements PartnerTypeRepositoryInterface
{
    public const CACHE_KEY = 'partner-types.active';

    public function __construct(PartnerType $model)
    {
        parent::__construct($model);
    }

    public function allOrdered(): Collection
    {
        return $this->model->withCount('partners')
            ->ordered()
            ->get();
    }

    public function activeOrdered(): Collection
    {
        return Cache::remember(self::CACHE_KEY, now()->addHour(), function () {
            return $this->model->active()
                ->withCount(['partners' => fn ($q) => $q->active()])
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
