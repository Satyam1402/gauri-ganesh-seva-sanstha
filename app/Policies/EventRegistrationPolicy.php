<?php

namespace App\Policies;

use App\Models\EventRegistration;
use App\Models\User;

class EventRegistrationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage events');
    }

    public function view(User $user, EventRegistration $eventRegistration): bool
    {
        return $user->can('manage events');
    }

    public function update(User $user, EventRegistration $eventRegistration): bool
    {
        return $user->can('manage events');
    }

    public function delete(User $user, EventRegistration $eventRegistration): bool
    {
        return $user->can('manage events');
    }
}
