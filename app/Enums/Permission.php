<?php

namespace App\Enums;

enum Permission: string
{
    case ManageUsers = 'manage users';
    case ManageRoles = 'manage roles';
    case ManageHomepage = 'manage homepage';
    case ManageAbout = 'manage about';
    case ManageActivities = 'manage activities';
    case ManageGallery = 'manage gallery';
    case ManageBlog = 'manage blog';
    case ManageEvents = 'manage events';
    case ManageDonations = 'manage donations';
    case ManageVolunteers = 'manage volunteers';
    case ManageReports = 'manage reports';
    case ManageSettings = 'manage settings';
    case ManageContactMessages = 'manage contact messages';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
