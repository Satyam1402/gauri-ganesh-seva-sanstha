<?php

namespace App\Jobs;

use App\Models\Backup;
use App\Services\BackupService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

/**
 * Executes one pending backup off the web request. Never retried
 * automatically — a failed run is recorded and the admin retries it as a
 * new run so the failure stays on the record.
 */
class RunBackupJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout;

    public function __construct(public int $backupId)
    {
        $this->timeout = (int) config('backup.app.job_timeout', 3600);
    }

    /**
     * One backup at a time: two concurrent mysqldumps would double the
     * load and race for the temporary directory.
     *
     * @return list<object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('backups'))->releaseAfter(60)->expireAfter($this->timeout)];
    }

    public function handle(BackupService $backups): void
    {
        $backup = Backup::query()->find($this->backupId);

        if ($backup === null) {
            return;
        }

        $backups->run($backup);
    }

    /**
     * The worker killed the run (timeout, fatal error): make sure the row
     * does not stay "running" forever.
     */
    public function failed(?Throwable $exception): void
    {
        $backup = Backup::query()->find($this->backupId);

        if ($backup === null || $backup->status->isTerminal()) {
            return;
        }

        app(BackupService::class)->markFailed(
            $backup,
            $exception?->getMessage() ?: 'The backup job was terminated before it finished.',
        );
    }
}
