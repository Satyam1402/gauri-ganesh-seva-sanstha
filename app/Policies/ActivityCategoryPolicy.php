<?php

namespace App\Policies;

use App\Models\ActivityCategory;
use App\Models\User;

class ActivityCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage activities');
    }

    public function view(User $user, ActivityCategory $activityCategory): bool
    {
        return $user->can('manage activities');
    }

    public function create(User $user): bool
    {
        return $user->can('manage activities');
    }

    public function update(User $user, ActivityCategory $activityCategory): bool
    {
        return $user->can('manage activities');
    }

    public function delete(User $user, ActivityCategory $activityCategory): bool
    {
        return $user->can('manage activities');
    }
}
