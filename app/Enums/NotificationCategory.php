<?php

namespace App\Enums;

/**
 * Groups internal (admin) notifications and ties each group to the module
 * permission a user must hold to receive — and later see — it.
 */
enum NotificationCategory: string
{
    case Donation = 'donation';
    case Volunteer = 'volunteer';
    case Event = 'event';
    case Enquiry = 'enquiry';
    case Comment = 'comment';
    case System = 'system';
    case Backup = 'backup';

    public function label(): string
    {
        return match ($this) {
            self::Donation => 'Donations',
            self::Volunteer => 'Volunteers',
            self::Event => 'Event Registrations',
            self::Enquiry => 'Enquiries',
            self::Comment => 'Blog Comments',
            self::System => 'System',
            self::Backup => 'Backups',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Donation => 'success',
            self::Volunteer => 'accent',
            self::Event => 'neutral',
            self::Enquiry => 'warning',
            self::Comment => 'neutral',
            self::System => 'error',
            self::Backup => 'warning',
        };
    }

    /**
     * The permission that authorises a user for this category. Super Admin
     * passes through Gate::before like everywhere else.
     */
    public function permission(): Permission
    {
        return match ($this) {
            self::Donation => Permission::ManageDonations,
            self::Volunteer => Permission::ManageVolunteers,
            self::Event => Permission::ManageEvents,
            self::Enquiry => Permission::ManageContactMessages,
            self::Comment => Permission::ManageBlog,
            self::System => Permission::ManageSettings,
            self::Backup => Permission::ManageBackups,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_combine(
            array_map(fn (self $case) => $case->value, self::cases()),
            array_map(fn (self $case) => $case->label(), self::cases()),
        );
    }
}
