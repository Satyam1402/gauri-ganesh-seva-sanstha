<?php

namespace App\Enums;

enum VolunteerApplicationStatus: string
{
    case Pending = 'pending';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case OnHold = 'on_hold';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::UnderReview => 'Under Review',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::OnHold => 'On Hold',
            self::Archived => 'Archived',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::UnderReview => 'accent',
            self::Approved => 'success',
            self::Rejected => 'error',
            self::OnHold => 'neutral',
            self::Archived => 'neutral',
        };
    }

    /**
     * Statuses considered "open" — an applicant with an application in one of
     * these states cannot submit another one with the same email address.
     */
    public static function openValues(): array
    {
        return [self::Pending->value, self::UnderReview->value, self::OnHold->value];
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
