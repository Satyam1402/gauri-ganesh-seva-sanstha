<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'Super Admin';
    case Admin = 'Admin';
    case ContentManager = 'Content Manager';
    case VolunteerManager = 'Volunteer Manager';
    case DonationManager = 'Donation Manager';
    case Editor = 'Editor';
    case Viewer = 'Viewer';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
