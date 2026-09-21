<?php

namespace App\View\Components\Admin;

use App\Interfaces\NotificationRepositoryInterface;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Topbar bell (<x-admin.notification-bell />): unread count plus the
 * latest few notifications the signed-in admin is authorised to see.
 * Class-based so the query lives here, not in the layout partial.
 */
class NotificationBell extends Component
{
    public function __construct(private NotificationRepositoryInterface $notifications) {}

    public function render(): View
    {
        $user = auth()->user();

        return view('components.admin.notification-bell', [
            'unreadCount' => $this->notifications->unreadCountFor($user),
            'recent' => $this->notifications->recentFor($user, (int) config('notifications.dropdown_limit', 6)),
        ]);
    }
}
