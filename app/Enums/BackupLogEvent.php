<?php

namespace App\Enums;

enum BackupLogEvent: string
{
    case BackupQueued = 'backup_queued';
    case BackupCompleted = 'backup_completed';
    case BackupFailed = 'backup_failed';
    case BackupDownloaded = 'backup_downloaded';
    case BackupDeleted = 'backup_deleted';
    case BackupExpired = 'backup_expired';
    case CleanupRun = 'cleanup_run';
    case RestoreInitiated = 'restore_initiated';
    case RestoreCompleted = 'restore_completed';
    case RestoreFailed = 'restore_failed';

    public function label(): string
    {
        return match ($this) {
            self::BackupQueued => 'Backup queued',
            self::BackupCompleted => 'Backup completed',
            self::BackupFailed => 'Backup failed',
            self::BackupDownloaded => 'Backup downloaded',
            self::BackupDeleted => 'Backup deleted',
            self::BackupExpired => 'Backup expired',
            self::CleanupRun => 'Retention cleanup',
            self::RestoreInitiated => 'Restore initiated',
            self::RestoreCompleted => 'Restore completed',
            self::RestoreFailed => 'Restore failed',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::BackupCompleted, self::RestoreCompleted => 'success',
            self::BackupFailed, self::RestoreFailed => 'error',
            self::RestoreInitiated, self::BackupDeleted => 'warning',
            self::BackupDownloaded => 'accent',
            default => 'neutral',
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
