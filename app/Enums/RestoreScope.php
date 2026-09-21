<?php

namespace App\Enums;

enum RestoreScope: string
{
    case Database = 'database';
    case Files = 'files';
    case Both = 'both';

    public function label(): string
    {
        return match ($this) {
            self::Database => 'Database only',
            self::Files => 'Uploaded files only',
            self::Both => 'Database and uploaded files',
        };
    }

    public function includesDatabase(): bool
    {
        return $this !== self::Files;
    }

    public function includesFiles(): bool
    {
        return $this !== self::Database;
    }

    /**
     * Scopes a backup of the given type can serve.
     *
     * @return list<self>
     */
    public static function availableFor(BackupType $type): array
    {
        return match ($type) {
            BackupType::Database => [self::Database],
            BackupType::Files => [self::Files],
            BackupType::Full => [self::Database, self::Files, self::Both],
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
