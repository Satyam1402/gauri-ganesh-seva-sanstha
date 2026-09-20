<?php

namespace App\Policies;

use App\Models\PartnerType;
use App\Models\User;

class PartnerTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage partners');
    }

    public function view(User $user, PartnerType $partnerType): bool
    {
        return $user->can('manage partners');
    }

    public function create(User $user): bool
    {
        return $user->can('manage partners');
    }

    public function update(User $user, PartnerType $partnerType): bool
    {
        return $user->can('manage partners');
    }

    public function delete(User $user, PartnerType $partnerType): bool
    {
        return $user->can('manage partners');
    }
}
