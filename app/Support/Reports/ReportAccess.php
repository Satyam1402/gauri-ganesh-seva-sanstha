<?php

namespace App\Support\Reports;

use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Report visibility derived from the existing RBAC permissions — no role
 * names anywhere. "manage reports" opens everything; module permissions
 * open the matching report (Donation Manager → donation reports, etc.).
 */
class ReportAccess
{
    /**
     * Gate ability => permissions, any one of which grants it.
     *
     * @var array<string, list<string>>
     */
    public const ABILITIES = [
        'view-donation-reports' => ['manage reports', 'manage donations'],
        'view-volunteer-reports' => ['manage reports', 'manage volunteers'],
        'view-event-reports' => ['manage reports', 'manage events'],
        'view-contact-reports' => ['manage reports', 'manage contact messages'],
        'view-activity-reports' => ['manage reports', 'manage activities'],
        'view-blog-reports' => ['manage reports', 'manage blog'],
        'view-gallery-reports' => ['manage reports', 'manage gallery'],
    ];

    /**
     * Report key (URL segment) => Gate ability.
     *
     * @var array<string, string>
     */
    public const REPORTS = [
        'donations' => 'view-donation-reports',
        'volunteers' => 'view-volunteer-reports',
        'events' => 'view-event-reports',
        'contacts' => 'view-contact-reports',
        'activities' => 'view-activity-reports',
        'blog' => 'view-blog-reports',
        'gallery' => 'view-gallery-reports',
    ];

    public static function register(): void
    {
        foreach (self::ABILITIES as $ability => $permissions) {
            Gate::define($ability, function (User $user) use ($permissions): bool {
                foreach ($permissions as $permission) {
                    if ($user->can($permission)) {
                        return true;
                    }
                }

                return false;
            });
        }

        Gate::define('view-any-reports', function (User $user): bool {
            foreach (array_keys(self::ABILITIES) as $ability) {
                if ($user->can($ability)) {
                    return true;
                }
            }

            return false;
        });
    }

    /**
     * Report keys the user may open.
     *
     * @return list<string>
     */
    public static function allowedFor(User $user): array
    {
        return array_values(array_filter(array_keys(self::REPORTS), fn (string $key) => $user->can(self::REPORTS[$key])));
    }
}
