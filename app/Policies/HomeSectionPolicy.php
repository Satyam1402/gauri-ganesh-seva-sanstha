<?php

namespace App\Policies;

use App\Models\HomeSection;
use App\Models\User;

class HomeSectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage homepage');
    }

    public function update(User $user, HomeSection $homeSection): bool
    {
        return $user->can('manage homepage');
    }
}
