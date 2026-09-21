<?php

namespace App\Enums;

enum BackupTrigger: string
{
    case Manual = 'manual';
    case Scheduled = 'scheduled';
    case PreRestore = 'pre_restore';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Scheduled => 'Scheduled',
            self::PreRestore => 'Safety copy (before restore)',
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
