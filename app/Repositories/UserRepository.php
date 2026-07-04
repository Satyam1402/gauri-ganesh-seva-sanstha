<?php

namespace App\Repositories;

use App\Interfaces\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function search(?string $term, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with('roles')
            ->when($term, fn ($query) => $query->where(
                fn ($query) => $query->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
            ))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }
}
