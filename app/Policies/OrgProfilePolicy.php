<?php

namespace App\Policies;

use App\Models\OrgProfile;
use App\Models\User;

class OrgProfilePolicy
{
    public function update(User $user, OrgProfile $orgProfile): bool
    {
        return $user->can('manage about');
    }
}
