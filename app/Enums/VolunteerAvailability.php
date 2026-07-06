<?php

namespace App\Enums;

enum VolunteerAvailability: string
{
    case Weekdays = 'weekdays';
    case Weekends = 'weekends';
    case Both = 'both';
    case Flexible = 'flexible';

    public function label(): string
    {
        return match ($this) {
            self::Weekdays => 'Weekdays',
            self::Weekends => 'Weekends',
            self::Both => 'Weekdays & Weekends',
            self::Flexible => 'Flexible / On Call',
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
