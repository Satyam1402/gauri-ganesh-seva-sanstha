<?php

namespace App\Enums;

enum BackupType: string
{
    case Database = 'database';
    case Files = 'files';
    case Full = 'full';

    public function label(): string
    {
        return match ($this) {
            self::Database => 'Database',
            self::Files => 'Files',
            self::Full => 'Full (database + files)',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Database => 'accent',
            self::Files => 'neutral',
            self::Full => 'success',
        };
    }

    /**
     * Short prefix used in archive filenames, e.g. db-2026-09-21-01-30-00.zip.
     */
    public function filePrefix(): string
    {
        return match ($this) {
            self::Database => 'db',
            self::Files => 'files',
            self::Full => 'full',
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
     * Options passed to spatie's `backup:run`.
     *
     * @return array<string, bool>
     */
    public function artisanOptions(): array
    {
        return match ($this) {
            self::Database => ['--only-db' => true],
            self::Files => ['--only-files' => true],
            self::Full => [],
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
