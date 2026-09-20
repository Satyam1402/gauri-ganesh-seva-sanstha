<?php

namespace App\Policies;

use App\Models\Partner;
use App\Models\User;

class PartnerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage partners');
    }

    public function view(User $user, Partner $partner): bool
    {
        return $user->can('manage partners');
    }

    public function create(User $user): bool
    {
        return $user->can('manage partners');
    }

    public function update(User $user, Partner $partner): bool
    {
        return $user->can('manage partners');
    }

    public function delete(User $user, Partner $partner): bool
    {
        return $user->can('manage partners');
    }

    public function restore(User $user, Partner $partner): bool
    {
        return $user->can('manage partners');
    }
}
