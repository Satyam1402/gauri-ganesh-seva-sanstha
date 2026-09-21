<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

/**
 * Applies the retention policy from config/backup.php (spatie `backup:clean`,
 * which never removes the newest archive) and marks rows whose archive is
 * gone as expired. Scheduled daily; also available from the admin panel.
 */
class CleanBackups extends Command
{
    protected $signature = 'backups:clean';

    protected $description = 'Apply the backup retention policy and reconcile the backup history.';

    public function handle(BackupService $backups): int
    {
        $result = $backups->cleanup();

        if ($result['error'] !== null) {
            $this->error('Cleanup failed: '.$result['error']);

            return self::FAILURE;
        }

        $this->info("Retention policy applied; {$result['expired']} backup(s) marked expired.");

        return self::SUCCESS;
    }
}
