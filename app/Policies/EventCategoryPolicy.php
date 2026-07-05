<?php

namespace App\Policies;

use App\Models\EventCategory;
use App\Models\User;

class EventCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage events');
    }

    public function view(User $user, EventCategory $eventCategory): bool
    {
        return $user->can('manage events');
    }

    public function create(User $user): bool
    {
        return $user->can('manage events');
    }

    public function update(User $user, EventCategory $eventCategory): bool
    {
        return $user->can('manage events');
    }

    public function delete(User $user, EventCategory $eventCategory): bool
    {
        return $user->can('manage events');
    }
}
