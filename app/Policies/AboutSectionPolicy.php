<?php

namespace App\Policies;

use App\Models\AboutSection;
use App\Models\User;

class AboutSectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage about');
    }

    public function update(User $user, AboutSection $aboutSection): bool
    {
        return $user->can('manage about');
    }
}
