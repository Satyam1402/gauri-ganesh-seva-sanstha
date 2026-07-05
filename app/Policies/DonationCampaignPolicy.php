<?php

namespace App\Policies;

use App\Models\DonationCampaign;
use App\Models\User;

class DonationCampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage donations');
    }

    public function view(User $user, DonationCampaign $donationCampaign): bool
    {
        return $user->can('manage donations');
    }

    public function create(User $user): bool
    {
        return $user->can('manage donations');
    }

    public function update(User $user, DonationCampaign $donationCampaign): bool
    {
        return $user->can('manage donations');
    }

    public function delete(User $user, DonationCampaign $donationCampaign): bool
    {
        return $user->can('manage donations');
    }

    public function restore(User $user, DonationCampaign $donationCampaign): bool
    {
        return $user->can('manage donations');
    }
}
