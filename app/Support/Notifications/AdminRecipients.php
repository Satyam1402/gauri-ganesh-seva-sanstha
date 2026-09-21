<?php

namespace App\Support\Notifications;

use App\Enums\NotificationCategory;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Resolves who may receive an internal notification.
 *
 * Recipients are the active admin users who can perform the category's
 * permission — evaluated through the Gate so Super Admin (Gate::before)
 * and any future policy rules are honoured without naming roles here.
 * The admin user base is small, so loading it once per dispatch is far
 * cheaper than a hand-rolled role/permission join that could drift from
 * the Gate.
 */
class AdminRecipients
{
    /**
     * @return Collection<int, User>
     */
    public function for(NotificationCategory $category): Collection
    {
        $ability = $category->permission()->value;

        return User::query()
            ->where('status', 'active')
            ->whereNotNull('email')
            ->with(['roles.permissions', 'permissions'])
            ->get()
            ->filter(fn (User $user) => $user->can($ability))
            ->values();
    }

    /**
     * An optional shared mailbox configured for a module (e.g. donations@…),
     * returned only when it is set, well-formed, and not already covered by
     * one of the user recipients.
     *
     * @param  Collection<int, User>  $users
     */
    public function teamInbox(?string $address, Collection $users): ?string
    {
        $address = trim((string) $address);

        if ($address === '' || filter_var($address, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        $known = $users->pluck('email')->map(fn (string $email) => mb_strtolower($email));

        return $known->contains(mb_strtolower($address)) ? null : $address;
    }
}
