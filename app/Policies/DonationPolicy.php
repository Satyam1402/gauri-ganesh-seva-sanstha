<?php

namespace App\Policies;

use App\Models\Donation;
use App\Models\User;

class DonationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage donations');
    }

    public function view(User $user, Donation $donation): bool
    {
        return $user->can('manage donations');
    }

    public function create(User $user): bool
    {
        return $user->can('manage donations');
    }

    public function update(User $user, Donation $donation): bool
    {
        return $user->can('manage donations');
    }

    public function delete(User $user, Donation $donation): bool
    {
        return $user->can('manage donations');
    }

    public function restore(User $user, Donation $donation): bool
    {
        return $user->can('manage donations');
    }

    public function viewReports(User $user): bool
    {
        return $user->can('manage reports') || $user->can('manage donations');
    }
}
