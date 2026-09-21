<?php

namespace App\Services;

use App\Interfaces\NotificationRepositoryInterface;
use App\Models\User;
use App\Notifications\AdminNotification;
use App\Notifications\BaseNotification;
use App\Notifications\System\SystemAlert;
use App\Support\Notifications\AdminRecipients;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * The single entry point other services use to send notifications.
 *
 * Dispatch never throws: a donation, application or enquiry has already
 * been saved by the time we get here, and a mail/queue outage must not
 * roll that back or surface a technical error to a visitor. Failures are
 * logged with enough context to resend by hand.
 */
class NotificationService
{
    public function __construct(
        private NotificationRepositoryInterface $notifications,
        private AdminRecipients $recipients,
    ) {}

    /**
     * Notify a public recipient — a Donation, VolunteerApplication,
     * EventRegistration or ContactEnquiry (all Notifiable) — by email.
     */
    public function send(object $notifiable, BaseNotification $notification): void
    {
        $this->guard(fn () => $notifiable->notify($notification), $notification);
    }

    /**
     * Notify every active admin who holds the notification's permission,
     * plus an optional shared team inbox (mail only).
     */
    public function notifyAdmins(AdminNotification $notification, ?string $teamInbox = null): void
    {
        $this->guard(function () use ($notification, $teamInbox): void {
            $users = $this->recipients->for($notification::category());

            if ($users->isNotEmpty()) {
                Notification::send($users, $notification);
            }

            $inbox = $this->recipients->teamInbox($teamInbox, $users);

            if ($inbox !== null) {
                Notification::route('mail', $inbox)->notify($notification);
            }
        }, $notification);
    }

    /**
     * Record an in-app system alert for users who manage settings.
     * Delivered synchronously (database channel only) so it works even
     * when the queue or mailer is what's broken.
     */
    public function systemAlert(string $title, string $message, ?string $url = null): void
    {
        $alert = new SystemAlert($title, $message, $url);

        $this->guard(function () use ($alert): void {
            $users = $this->recipients->for($alert::category());

            if ($users->isNotEmpty()) {
                Notification::sendNow($users, $alert, ['database']);
            }
        }, $alert);
    }

    public function markAsRead(User $user, string $id): bool
    {
        $notification = $this->notifications->findFor($user, $id);

        if ($notification === null) {
            return false;
        }

        $this->notifications->markAsRead($notification);

        return true;
    }

    public function markAllAsRead(User $user): int
    {
        return $this->notifications->markAllAsReadFor($user);
    }

    public function delete(User $user, string $id): bool
    {
        $notification = $this->notifications->findFor($user, $id);

        if ($notification === null) {
            return false;
        }

        $this->notifications->delete($notification);

        return true;
    }

    public function clearRead(User $user): int
    {
        return $this->notifications->deleteReadFor($user);
    }

    /**
     * Resolve the target URL of a notification and mark it read in one go
     * (used by the bell/list "open" links).
     */
    public function open(User $user, string $id): ?string
    {
        $notification = $this->notifications->findFor($user, $id);

        if ($notification === null) {
            return null;
        }

        $this->notifications->markAsRead($notification);

        return $this->urlOf($notification);
    }

    public function urlOf(DatabaseNotification $notification): ?string
    {
        $url = $notification->data['url'] ?? null;

        return is_string($url) && $url !== '' ? $url : null;
    }

    private function guard(callable $dispatch, BaseNotification $notification): void
    {
        try {
            $dispatch();
        } catch (Throwable $e) {
            Log::error('Notification dispatch failed.', [
                'notification' => $notification::class,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
