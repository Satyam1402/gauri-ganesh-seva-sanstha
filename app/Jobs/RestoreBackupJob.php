<?php

namespace App\Jobs;

use App\Enums\BackupLogEvent;
use App\Enums\RestoreScope;
use App\Models\Backup;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

/**
 * Runs the controlled restore workflow (safety backup → restore → cache
 * flush) off the web request. Shares the "backups" lock with RunBackupJob
 * so nothing else touches the database or uploads while it runs.
 */
class RestoreBackupJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout;

    public function __construct(
        public int $backupId,
        public string $scope,
        public ?int $userId = null,
        public ?string $ip = null,
    ) {
        $this->timeout = (int) config('backup.app.job_timeout', 3600);
    }

    /**
     * @return list<object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('backups'))->releaseAfter(60)->expireAfter($this->timeout)];
    }

    public function handle(BackupService $backups): void
    {
        $backup = Backup::query()->find($this->backupId);
        $scope = RestoreScope::tryFrom($this->scope);

        if ($backup === null || $scope === null) {
            return;
        }

        $backups->performRestore($backup, $scope, $this->userId ? User::query()->find($this->userId) : null, $this->ip);
    }

    public function failed(?Throwable $exception): void
    {
        $backup = Backup::query()->find($this->backupId);

        app(BackupService::class)->log(
            BackupLogEvent::RestoreFailed,
            $backup,
            'Restore job terminated before it finished.',
            ['scope' => $this->scope, 'reason' => $exception?->getMessage()],
            $this->userId ? User::query()->find($this->userId) : null,
            $this->ip,
        );
    }
}
