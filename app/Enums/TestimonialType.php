<?php

namespace App\Enums;

enum TestimonialType: string
{
    case Beneficiary = 'beneficiary';
    case Donor = 'donor';
    case Volunteer = 'volunteer';
    case Partner = 'partner';
    case CommunityMember = 'community_member';

    public function label(): string
    {
        return match ($this) {
            self::Beneficiary => 'Beneficiary',
            self::Donor => 'Donor',
            self::Volunteer => 'Volunteer',
            self::Partner => 'Partner Organisation',
            self::CommunityMember => 'Community Member',
        };
    }

    /**
     * Plural heading used by the public page's type tabs and the reusable
     * section ("Beneficiary Stories", "Donor Feedback", ...).
     */
    public function pluralLabel(): string
    {
        return match ($this) {
            self::Beneficiary => 'Beneficiary Stories',
            self::Donor => 'Donor Feedback',
            self::Volunteer => 'Volunteer Voices',
            self::Partner => 'Partner Organisations',
            self::CommunityMember => 'Community Voices',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Beneficiary => 'accent',
            self::Donor => 'success',
            self::Volunteer => 'neutral',
            self::Partner => 'warning',
            self::CommunityMember => 'neutral',
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

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
