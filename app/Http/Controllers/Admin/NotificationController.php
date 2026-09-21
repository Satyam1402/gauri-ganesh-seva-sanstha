<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Interfaces\NotificationRepositoryInterface;
use App\Services\NotificationService;
use App\Support\Notifications\NotificationCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

/**
 * The signed-in admin's notification centre. Rows are already scoped to
 * the user and to the categories their permissions allow (repository +
 * policy), so no module permission middleware is needed on these routes.
 */
class NotificationController extends Controller
{
    public function __construct(
        private NotificationRepositoryInterface $notifications,
        private NotificationService $notificationService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', DatabaseNotification::class);

        $user = $request->user();
        $filters = $request->only(['status', 'category']);

        return view('admin.notifications.index', [
            'notifications' => $this->notifications->paginateFor($user, $filters, (int) config('notifications.per_page', 20)),
            'unreadCount' => $this->notifications->unreadCountFor($user),
            'categories' => NotificationCatalog::visibleCategoryOptions($user),
            'filters' => $filters,
        ]);
    }

    /**
     * Mark one notification read and jump to the record it points at.
     */
    public function open(Request $request, string $notification): RedirectResponse
    {
        $url = $this->notificationService->open($request->user(), $notification);

        if ($url === null) {
            return redirect()->route('admin.notifications.index');
        }

        return redirect()->to($url);
    }

    public function markAsRead(Request $request, string $notification): RedirectResponse
    {
        abort_unless($this->notificationService->markAsRead($request->user(), $notification), 404);

        return back()->with('status', 'Notification marked as read.');
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $count = $this->notificationService->markAllAsRead($request->user());

        return back()->with('status', $count === 0 ? 'No unread notifications.' : "{$count} notification(s) marked as read.");
    }

    public function destroy(Request $request, string $notification): RedirectResponse
    {
        abort_unless($this->notificationService->delete($request->user(), $notification), 404);

        return back()->with('status', 'Notification deleted.');
    }

    public function clearRead(Request $request): RedirectResponse
    {
        $count = $this->notificationService->clearRead($request->user());

        return back()->with('status', $count === 0 ? 'No read notifications to clear.' : "{$count} read notification(s) cleared.");
    }
}
