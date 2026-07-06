<?php

namespace App\Policies;

use App\Models\VolunteerApplication;
use App\Models\User;

class VolunteerApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage volunteers');
    }

    // The model parameters default to null so bulk actions can authorize
    // against the class (`authorize('update', VolunteerApplication::class)`).

    public function view(User $user, ?VolunteerApplication $volunteerApplication = null): bool
    {
        return $user->can('manage volunteers');
    }

    public function update(User $user, ?VolunteerApplication $volunteerApplication = null): bool
    {
        return $user->can('manage volunteers');
    }

    public function delete(User $user, ?VolunteerApplication $volunteerApplication = null): bool
    {
        return $user->can('manage volunteers');
    }

    public function restore(User $user, ?VolunteerApplication $volunteerApplication = null): bool
    {
        return $user->can('manage volunteers');
    }
}
