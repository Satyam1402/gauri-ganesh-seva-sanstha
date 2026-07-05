<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage events');
    }

    public function view(User $user, Event $event): bool
    {
        return $user->can('manage events');
    }

    public function create(User $user): bool
    {
        return $user->can('manage events');
    }

    public function update(User $user, Event $event): bool
    {
        return $user->can('manage events');
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->can('manage events');
    }

    public function restore(User $user, Event $event): bool
    {
        return $user->can('manage events');
    }
}
