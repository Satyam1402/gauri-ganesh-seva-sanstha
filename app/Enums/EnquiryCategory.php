<?php

namespace App\Enums;

enum EnquiryCategory: string
{
    case General = 'general';
    case Donation = 'donation';
    case Volunteer = 'volunteer';
    case Partnership = 'partnership';
    case Media = 'media';
    case Complaint = 'complaint';
    case Suggestion = 'suggestion';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::General => 'General Enquiry',
            self::Donation => 'Donation',
            self::Volunteer => 'Volunteering',
            self::Partnership => 'Partnership / CSR',
            self::Media => 'Media & Press',
            self::Complaint => 'Complaint',
            self::Suggestion => 'Suggestion',
            self::Other => 'Other',
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
