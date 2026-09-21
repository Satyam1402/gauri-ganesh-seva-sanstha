<?php

namespace App\Contracts;

use App\Enums\RestoreScope;
use App\Models\Backup;
use App\Support\Backups\ArchiveInfo;

/**
 * Validates and restores a backup archive. Implementations must throw a
 * descriptive exception on any failure; they never partially succeed
 * silently. Tests bind a fake — real restores never run in the suite.
 */
interface BackupRestorer
{
    /**
     * Open the archive (decrypting when needed) and report what it holds.
     * Throws when the archive is missing, unreadable or the password is wrong.
     */
    public function inspect(Backup $backup): ArchiveInfo;

    /**
     * Whether this environment can restore the given scope at all
     * (e.g. database restores need a MySQL/MariaDB connection + mysql client).
     */
    public function supports(RestoreScope $scope): bool;

    public function restore(Backup $backup, RestoreScope $scope): void;
}
