<?php

namespace App\Policies;

use App\Models\ContactEnquiry;
use App\Models\User;

class ContactEnquiryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage contact messages');
    }

    // The model parameters default to null so bulk actions can authorize
    // against the class (`authorize('update', ContactEnquiry::class)`).

    public function view(User $user, ?ContactEnquiry $contactEnquiry = null): bool
    {
        return $user->can('manage contact messages');
    }

    public function update(User $user, ?ContactEnquiry $contactEnquiry = null): bool
    {
        return $user->can('manage contact messages');
    }

    public function reply(User $user, ?ContactEnquiry $contactEnquiry = null): bool
    {
        return $user->can('manage contact messages');
    }

    public function delete(User $user, ?ContactEnquiry $contactEnquiry = null): bool
    {
        return $user->can('manage contact messages');
    }

    public function restore(User $user, ?ContactEnquiry $contactEnquiry = null): bool
    {
        return $user->can('manage contact messages');
    }
}
