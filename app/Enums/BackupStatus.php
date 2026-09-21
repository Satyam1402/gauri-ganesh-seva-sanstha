<?php

namespace App\Enums;

enum BackupStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case Deleted = 'deleted';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Running => 'Running',
            self::Completed => 'Completed',
            self::Failed => 'Failed',
            self::Deleted => 'Deleted',
            self::Expired => 'Expired',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Running => 'accent',
            self::Completed => 'success',
            self::Failed => 'error',
            self::Deleted => 'neutral',
            self::Expired => 'neutral',
        };
    }

    /**
     * Statuses whose archive is still expected to exist on disk.
     */
    public function hasArchive(): bool
    {
        return $this === self::Completed;
    }

    public function isTerminal(): bool
    {
        return ! in_array($this, [self::Pending, self::Running], true);
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
