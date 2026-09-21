<?php

namespace App\Support\Notifications;

use App\Enums\NotificationCategory;
use App\Models\User;
use App\Notifications\AdminNotification;
use App\Notifications\Backups\BackupFailedAlert;
use App\Notifications\Backups\BackupRestoredAlert;
use App\Notifications\Backups\RestoreFailedAlert;
use App\Notifications\Blog\CommentAwaitingModerationAlert;
use App\Notifications\Contact\NewEnquiryAlert;
use App\Notifications\Donations\NewDonationAlert;
use App\Notifications\Events\NewEventRegistrationAlert;
use App\Notifications\System\SystemAlert;
use App\Notifications\Volunteers\NewVolunteerApplicationAlert;

/**
 * The registry of in-app (database) notification classes.
 *
 * Laravel stores the notification class name in notifications.type; this
 * catalogue maps those class names back to their category so the admin
 * notification centre can (a) filter by category and (b) hide rows whose
 * permission the viewer no longer holds — a user who lost "manage donations"
 * after a donation alert was delivered must not keep seeing it.
 */
class NotificationCatalog
{
    /**
     * @var list<class-string<AdminNotification>>
     */
    private const CLASSES = [
        NewDonationAlert::class,
        NewVolunteerApplicationAlert::class,
        NewEventRegistrationAlert::class,
        NewEnquiryAlert::class,
        CommentAwaitingModerationAlert::class,
        SystemAlert::class,
        BackupFailedAlert::class,
        RestoreFailedAlert::class,
        BackupRestoredAlert::class,
    ];

    /**
     * @return list<class-string<AdminNotification>>
     */
    public static function classes(): array
    {
        return self::CLASSES;
    }

    /**
     * @return list<class-string<AdminNotification>>
     */
    public static function classesFor(NotificationCategory $category): array
    {
        return array_values(array_filter(
            self::CLASSES,
            fn (string $class) => $class::category() === $category,
        ));
    }

    /**
     * Notification classes the user is currently authorised to see.
     *
     * @return list<class-string<AdminNotification>>
     */
    public static function visibleClassesFor(User $user): array
    {
        return array_values(array_filter(
            self::CLASSES,
            fn (string $class) => $user->can($class::category()->permission()->value),
        ));
    }

    /**
     * Categories the user is authorised for — drives the filter dropdown.
     *
     * @return array<string, string>  value => label
     */
    public static function visibleCategoryOptions(User $user): array
    {
        $options = [];

        foreach (NotificationCategory::cases() as $category) {
            if ($user->can($category->permission()->value)) {
                $options[$category->value] = $category->label();
            }
        }

        return $options;
    }
}
