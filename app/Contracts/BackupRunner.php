<?php

namespace App\Contracts;

use App\Models\Backup;
use App\Support\Backups\BackupRunResult;

/**
 * Produces the archive for a backup run. The production implementation
 * shells out to spatie/laravel-backup; tests bind a fake that writes a
 * small archive to a faked disk, so no mysqldump is ever needed in CI.
 */
interface BackupRunner
{
    public function run(Backup $backup): BackupRunResult;
}
