<?php

namespace App\Services;

use App\Contracts\BackupRestorer;
use App\Contracts\BackupRunner;
use App\Enums\BackupLogEvent;
use App\Enums\BackupStatus;
use App\Enums\BackupTrigger;
use App\Enums\BackupType;
use App\Enums\RestoreScope;
use App\Exceptions\BackupOperationException;
use App\Interfaces\BackupRepositoryInterface;
use App\Jobs\RestoreBackupJob;
use App\Jobs\RunBackupJob;
use App\Models\Backup;
use App\Models\BackupLog;
use App\Models\User;
use App\Notifications\Backups\BackupFailedAlert;
use App\Notifications\Backups\BackupRestoredAlert;
use App\Notifications\Backups\RestoreFailedAlert;
use App\Support\Backups\ArchiveInfo;
use App\Support\Backups\SecretScrubber;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Owns every backup and restore operation: queueing runs, executing them
 * (from the job or the scheduler), retention cleanup, downloads, deletion
 * and the controlled restore workflow. Every operation writes to the
 * backup audit log; failures go to backup managers through the existing
 * notification system.
 */
class BackupService
{
    public function __construct(
        private BackupRepositoryInterface $backups,
        private BackupRunner $runner,
        private BackupRestorer $restorer,
        private NotificationService $notifier,
        private SecretScrubber $scrubber,
    ) {}

    // ── creating backups ──────────────────────────────────────────────────

    /**
     * Record a pending run and push it onto the queue (runs inline when the
     * queue connection is "sync"). Manual runs from the panel use this.
     */
    public function queue(BackupType $type, BackupTrigger $trigger, ?User $user = null, ?string $ip = null): Backup
    {
        $backup = $this->createRow($type, $trigger, $user);

        $this->log(BackupLogEvent::BackupQueued, $backup, ucfirst($type->label()).' backup queued.', [
            'trigger' => $trigger->value,
        ], $user, $ip);

        RunBackupJob::dispatch($backup->id)->onQueue((string) config('backup.app.queue', 'default'));

        return $backup;
    }

    /**
     * Create and run a backup right now in the current process. Used by
     * the scheduler (already a background process) and for the safety copy
     * taken before a restore.
     */
    public function runNow(BackupType $type, BackupTrigger $trigger, ?User $user = null, ?string $ip = null): Backup
    {
        $backup = $this->createRow($type, $trigger, $user);

        $this->log(BackupLogEvent::BackupQueued, $backup, ucfirst($type->label()).' backup started.', [
            'trigger' => $trigger->value,
        ], $user, $ip);

        return $this->run($backup);
    }

    /**
     * Execute a pending run. Safe to call from the job or directly.
     */
    public function run(Backup $backup): Backup
    {
        if ($backup->status->isTerminal()) {
            return $backup;
        }

        $backup->forceFill([
            'status' => BackupStatus::Running->value,
            'started_at' => now(),
        ])->save();

        try {
            $result = $this->runner->run($backup);
        } catch (Throwable $e) {
            return $this->markFailed($backup, $e->getMessage());
        }

        if (! $result->success) {
            return $this->markFailed($backup, $result->error ?? 'Unknown error.');
        }

        $backup->forceFill([
            'status' => BackupStatus::Completed->value,
            'path' => $result->path,
            'size_bytes' => $result->sizeBytes,
            'disks' => $result->disks,
            'completed_at' => now(),
            'failure_reason' => null,
        ])->save();

        $this->log(BackupLogEvent::BackupCompleted, $backup, ucfirst($backup->type->label()).' backup completed ('.$backup->sizeForHumans().').', [
            'size_bytes' => $backup->size_bytes,
            'disks' => $backup->disks,
            'encrypted' => $backup->encrypted,
        ]);

        return $backup;
    }

    /**
     * Mark a run failed (also used by the job's failed() hook when the
     * worker kills a run that exceeded its timeout).
     */
    public function markFailed(Backup $backup, string $reason, bool $notify = true): Backup
    {
        $reason = (string) $this->scrubber->scrub($reason);

        $backup->forceFill([
            'status' => BackupStatus::Failed->value,
            'failure_reason' => $reason,
            'completed_at' => now(),
        ])->save();

        Log::error('Backup failed.', ['backup_id' => $backup->id, 'type' => $backup->type->value, 'reason' => $reason]);

        $this->log(BackupLogEvent::BackupFailed, $backup, ucfirst($backup->type->label()).' backup failed.', [
            'reason' => $reason,
        ]);

        if ($notify) {
            $this->notifier->notifyAdmins(new BackupFailedAlert($backup));
        }

        return $backup;
    }

    /**
     * A failed run is retried as a brand-new run so the failure stays on
     * record.
     */
    public function retry(Backup $failed, ?User $user = null, ?string $ip = null): Backup
    {
        if (! $failed->isFailed()) {
            throw new BackupOperationException('Only failed backups can be retried.');
        }

        return $this->queue($failed->type, BackupTrigger::Manual, $user, $ip);
    }

    // ── downloads & deletion ──────────────────────────────────────────────

    public function download(Backup $backup, User $user, ?string $ip = null): StreamedResponse
    {
        $diskName = $this->diskHolding($backup);

        if ($diskName === null) {
            throw new BackupOperationException('The archive for this backup no longer exists on any disk.');
        }

        $this->log(BackupLogEvent::BackupDownloaded, $backup, 'Backup archive downloaded.', [
            'disk' => $diskName,
            'size_bytes' => $backup->size_bytes,
        ], $user, $ip);

        return Storage::disk($diskName)->download($backup->path, $backup->filename, [
            'Content-Type' => 'application/zip',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * Delete the archive from every disk and mark the row. Refuses to
     * remove the last remaining valid backup.
     */
    public function delete(Backup $backup, ?User $user = null, ?string $ip = null): Backup
    {
        if ($backup->isInProgress()) {
            throw new BackupOperationException('This backup is still running.');
        }

        if ($backup->isCompleted() && $this->isLastValidBackup($backup)) {
            throw new BackupOperationException('This is the only remaining valid backup — create another one before deleting it.');
        }

        $removedFrom = [];

        foreach ($this->disksFor($backup) as $diskName) {
            if ($backup->path !== null && Storage::disk($diskName)->exists($backup->path)) {
                Storage::disk($diskName)->delete($backup->path);
                $removedFrom[] = $diskName;
            }
        }

        $backup->forceFill(['status' => BackupStatus::Deleted->value])->save();

        $this->log(BackupLogEvent::BackupDeleted, $backup, 'Backup deleted.', [
            'disks' => $removedFrom,
            'size_bytes' => $backup->size_bytes,
        ], $user, $ip);

        return $backup;
    }

    // ── retention ─────────────────────────────────────────────────────────

    /**
     * Apply the retention policy (spatie `backup:clean`, which always keeps
     * the newest archive) and reconcile rows whose archive is gone.
     *
     * @return array{cleaned: bool, expired: int, error: ?string}
     */
    public function cleanup(?User $user = null): array
    {
        $error = null;
        $cleaned = false;

        try {
            $exitCode = Artisan::call('backup:clean', ['--disable-notifications' => true]);
            $cleaned = $exitCode === 0;

            if (! $cleaned) {
                $error = $this->scrubber->scrub(trim(Artisan::output())) ?: 'backup:clean exited with code '.$exitCode;
            }
        } catch (Throwable $e) {
            $error = $this->scrubber->scrub($e->getMessage());
        }

        $this->failStaleRuns((int) config('backup.app.job_timeout', 3600));
        $this->rescan();
        $expired = $this->reconcile();

        $this->log(BackupLogEvent::CleanupRun, null, $cleaned
            ? "Retention cleanup ran; {$expired} backup(s) expired."
            : 'Retention cleanup failed.', [
                'expired' => $expired,
                'error' => $error,
                'policy' => (array) config('backup.cleanup.default_strategy'),
            ], $user);

        if ($error !== null) {
            Log::error('Backup cleanup failed.', ['error' => $error]);
        }

        return ['cleaned' => $cleaned, 'expired' => $expired, 'error' => $error];
    }

    /**
     * Completed rows whose archive exists on no disk any more were removed
     * by retention (or by hand on the server) — mark them expired so the
     * panel never shows a download that would 404.
     */
    public function reconcile(): int
    {
        $expired = 0;

        foreach ($this->backups->completed() as $backup) {
            if ($this->diskHolding($backup) !== null) {
                continue;
            }

            $backup->forceFill(['status' => BackupStatus::Expired->value])->save();
            $this->log(BackupLogEvent::BackupExpired, $backup, 'Backup archive removed by the retention policy.');
            $expired++;
        }

        return $expired;
    }

    /**
     * Rows stuck in pending/running are lies: either the worker died or the
     * history table was just restored from a dump taken mid-run. Mark them
     * failed so the panel never shows a run that will never finish.
     *
     * @param  int|null  $olderThanSeconds  Only rows idle this long (null = all).
     * @return int Number of rows marked failed.
     */
    public function failStaleRuns(?int $olderThanSeconds = null, string $reason = 'The run never finished — the worker stopped before completing it.', bool $notify = true): int
    {
        $count = 0;

        foreach ($this->backups->inProgress() as $stale) {
            $since = $stale->started_at ?? $stale->created_at;

            if ($olderThanSeconds !== null && $since->gt(now()->subSeconds($olderThanSeconds))) {
                continue;
            }

            // A dump taken mid-run restores its own row as "running" — but the
            // archive is right there on disk, so complete it instead.
            $path = $stale->path ?? config('backup.backup.name').'/'.$stale->filename;

            if ($stale->filename !== null && Storage::disk($stale->disk)->exists($path)) {
                $stale->forceFill([
                    'status' => BackupStatus::Completed->value,
                    'path' => $path,
                    'size_bytes' => (int) Storage::disk($stale->disk)->size($path),
                    'disks' => $stale->disks ?: [$stale->disk],
                    'completed_at' => now()->setTimestamp(Storage::disk($stale->disk)->lastModified($path)),
                ])->save();

                $this->log(BackupLogEvent::BackupCompleted, $stale, 'Run recovered: its archive was found on disk.', ['path' => $path]);
            } else {
                $this->markFailed($stale, $reason, $notify);
            }

            $count++;
        }

        return $count;
    }

    /**
     * Register archives on the primary disk that have no history row —
     * after a database restore (the history table is part of what was
     * restored) or when someone ran spatie's `backup:run` directly.
     *
     * @return int Number of rows created.
     */
    public function rescan(): int
    {
        $disks = (array) config('backup.backup.destination.disks');
        $diskName = $disks[0] ?? 'backups';
        $directory = (string) config('backup.backup.name');
        $known = Backup::query()->whereNotNull('filename')->pluck('filename')->flip();
        $created = 0;

        try {
            $files = Storage::disk($diskName)->files($directory);
        } catch (Throwable $e) {
            Log::warning('Backup rescan could not list the backup disk.', ['error' => $e->getMessage()]);

            return 0;
        }

        foreach ($files as $path) {
            $filename = basename($path);

            if (! str_ends_with($filename, '.zip') || $known->has($filename)) {
                continue;
            }

            $type = match (true) {
                str_starts_with($filename, 'db-') => BackupType::Database,
                str_starts_with($filename, 'files-') => BackupType::Files,
                default => BackupType::Full,
            };

            $backup = Backup::query()->create([
                'type' => $type->value,
                'status' => BackupStatus::Completed->value,
                'trigger' => BackupTrigger::Manual->value,
                'disk' => $diskName,
                'filename' => $filename,
                'path' => $path,
                'size_bytes' => (int) Storage::disk($diskName)->size($path),
                'encrypted' => (string) config('backup.backup.password') !== '',
                'disks' => [$diskName],
                'completed_at' => now()->setTimestamp(Storage::disk($diskName)->lastModified($path)),
            ]);

            $this->log(BackupLogEvent::BackupCompleted, $backup, 'Existing archive registered from disk.', ['path' => $path]);
            $created++;
        }

        return $created;
    }

    /**
     * True when no other completed backup still has an archive on disk.
     */
    public function isLastValidBackup(Backup $backup): bool
    {
        foreach ($this->backups->completed() as $other) {
            if ($other->is($backup)) {
                continue;
            }

            if ($this->diskHolding($other) !== null) {
                return false;
            }
        }

        return true;
    }

    // ── restore ───────────────────────────────────────────────────────────

    /**
     * Validate the archive and environment before the confirmation page is
     * shown, so the admin sees exactly what can be restored.
     */
    public function inspectForRestore(Backup $backup): ArchiveInfo
    {
        if (! $backup->isCompleted()) {
            throw new BackupOperationException('Only completed backups can be restored.');
        }

        if ($this->diskHolding($backup) === null) {
            throw new BackupOperationException('The archive for this backup no longer exists on any disk.');
        }

        try {
            return $this->restorer->inspect($backup);
        } catch (Throwable $e) {
            throw new BackupOperationException('The archive failed validation: '.$this->scrubber->scrub($e->getMessage()));
        }
    }

    /**
     * Scopes the admin may pick for this backup in this environment.
     *
     * @return array<string, string>  value => label
     */
    public function restoreScopesFor(Backup $backup, ArchiveInfo $info): array
    {
        $options = [];

        foreach (RestoreScope::availableFor($backup->type) as $scope) {
            if ($scope->includesDatabase() && (! $info->hasDatabase() || ! $this->restorer->supports($scope))) {
                continue;
            }

            if ($scope->includesFiles() && ! $info->hasFiles()) {
                continue;
            }

            $options[$scope->value] = $scope->label();
        }

        return $options;
    }

    /**
     * Start the controlled restore: validate, log, and queue the job. The
     * request layer has already verified the typed confirmation and the
     * admin's current password.
     */
    public function requestRestore(Backup $backup, RestoreScope $scope, User $user, ?string $ip = null): void
    {
        $info = $this->inspectForRestore($backup);

        if (! array_key_exists($scope->value, $this->restoreScopesFor($backup, $info))) {
            throw new BackupOperationException('That restore scope is not available for this backup.');
        }

        if ($this->backups->inProgress()->isNotEmpty()) {
            throw new BackupOperationException('A backup is currently running. Wait for it to finish before restoring.');
        }

        $this->log(BackupLogEvent::RestoreInitiated, $backup, 'Restore initiated ('.$scope->label().').', [
            'scope' => $scope->value,
            'archive' => $backup->filename,
        ], $user, $ip);

        RestoreBackupJob::dispatch($backup->id, $scope->value, $user->id, $ip)
            ->onQueue((string) config('backup.app.queue', 'default'));
    }

    /**
     * Executed by RestoreBackupJob: take a safety copy, then restore.
     */
    public function performRestore(Backup $backup, RestoreScope $scope, ?User $user = null, ?string $ip = null): void
    {
        $safetyType = match (true) {
            $scope === RestoreScope::Both => BackupType::Full,
            $scope->includesFiles() => BackupType::Files,
            default => BackupType::Database,
        };

        try {
            $safety = $this->runNow($safetyType, BackupTrigger::PreRestore, $user, $ip);

            if (! $safety->isCompleted()) {
                throw new BackupOperationException('The safety backup taken before restoring failed, so the restore was not attempted.');
            }

            $this->restorer->restore($backup, $scope);
            $this->afterRestore();
            $this->restoreInitiationRecord($backup, $scope, $user, $ip);
        } catch (Throwable $e) {
            $reason = (string) $this->scrubber->scrub($e->getMessage());

            Log::error('Backup restore failed.', ['backup_id' => $backup->id, 'scope' => $scope->value, 'reason' => $reason]);

            $this->log(BackupLogEvent::RestoreFailed, $backup, 'Restore failed.', [
                'scope' => $scope->value,
                'reason' => $reason,
            ], $user, $ip);

            $this->notifier->notifyAdmins(new RestoreFailedAlert($backup, $reason));

            return;
        }

        $this->log(BackupLogEvent::RestoreCompleted, $backup, 'Restore completed ('.$scope->label().').', [
            'scope' => $scope->value,
            'safety_backup_id' => $safety->id,
        ], $user, $ip);

        $this->notifier->notifyAdmins(new BackupRestoredAlert($backup, $scope, $user?->name));
    }

    /**
     * A database restore replaces the audit log too, taking the
     * "restore initiated" entry with it. Put it back so the trail reads
     * initiated → completed.
     */
    private function restoreInitiationRecord(Backup $backup, RestoreScope $scope, ?User $user, ?string $ip): void
    {
        $exists = BackupLog::query()
            ->where('backup_id', $backup->id)
            ->where('event', BackupLogEvent::RestoreInitiated->value)
            ->exists();

        if (! $exists) {
            $this->log(BackupLogEvent::RestoreInitiated, $backup, 'Restore initiated ('.$scope->label().').', [
                'scope' => $scope->value,
                'archive' => $backup->filename,
                'note' => 'Re-recorded: the original entry was replaced by the restored database.',
            ], $user, $ip);
        }
    }

    /**
     * Everything cached from the database is now stale.
     */
    private function afterRestore(): void
    {
        try {
            Cache::flush();
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (Throwable $e) {
            Log::warning('Cache flush after restore failed.', ['error' => $e->getMessage()]);
        }

        // The dump also carried the queue tables as they were mid-run: those
        // rows are stale duplicates (their work already happened) and a
        // worker would replay them. Anything queued after the dump was lost
        // by the restore anyway.
        foreach (['jobs', 'job_batches'] as $table) {
            try {
                if (Schema::hasTable($table)) {
                    DB::table($table)->delete();
                }
            } catch (Throwable $e) {
                Log::warning("Could not clear the {$table} table after restore.", ['error' => $e->getMessage()]);
            }
        }

        // The history table came back from the archive too: re-register the
        // safety copy (and anything else) that only exists on disk now.
        $this->failStaleRuns(null, 'The run was in progress when the history was restored from a backup and can no longer complete.', notify: false);
        $this->rescan();
    }

    // ── audit log ─────────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $context  Non-sensitive details only.
     */
    public function log(BackupLogEvent $event, ?Backup $backup, string $message, array $context = [], ?User $user = null, ?string $ip = null): BackupLog
    {
        return $this->backups->createLog([
            'backup_id' => $backup?->id,
            'event' => $event->value,
            'user_id' => $user?->id,
            'ip_address' => $ip,
            'message' => mb_substr($message, 0, 500),
            'context' => $context === [] ? null : $context,
        ]);
    }

    // ── helpers ───────────────────────────────────────────────────────────

    /**
     * Read-only facts for the panel header.
     *
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $latest = $this->backups->latestCompleted();
        $scheduled = $this->backups->latestScheduled();

        return [
            'latest' => $latest,
            'latestScheduled' => $scheduled,
            'schedulerStale' => $scheduled === null || $scheduled->created_at->lt(now()->subDays(2)),
            'encrypted' => (string) config('backup.backup.password') !== '',
            'disks' => (array) config('backup.backup.destination.disks'),
            'retention' => (array) config('backup.cleanup.default_strategy'),
            'dumpBinaryConfigured' => (string) config('database.connections.'.config('database.default').'.dump.dump_binary_path') !== '',
            'restoreSupported' => $this->supportsDatabaseRestore(),
            'counts' => $this->backups->countsByStatus(),
        ];
    }

    public function supportsDatabaseRestore(): bool
    {
        return $this->restorer->supports(RestoreScope::Database);
    }

    /**
     * First disk that still holds the archive, or null.
     */
    public function diskHolding(Backup $backup): ?string
    {
        if ($backup->path === null) {
            return null;
        }

        foreach ($this->disksFor($backup) as $diskName) {
            try {
                if (Storage::disk($diskName)->exists($backup->path)) {
                    return $diskName;
                }
            } catch (Throwable) {
                // An unreachable remote disk must not break the panel.
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function disksFor(Backup $backup): array
    {
        return array_values(array_unique(array_merge([$backup->disk], (array) $backup->disks)));
    }

    private function createRow(BackupType $type, BackupTrigger $trigger, ?User $user): Backup
    {
        $disks = (array) config('backup.backup.destination.disks');
        // Second-resolution timestamp plus a short suffix so a safety copy and
        // a manual run started in the same second never collide.
        $filename = sprintf('%s-%s-%s.zip', $type->filePrefix(), now()->format('Y-m-d-H-i-s'), Str::lower(Str::random(4)));

        return Backup::query()->create([
            'type' => $type->value,
            'status' => BackupStatus::Pending->value,
            'trigger' => $trigger->value,
            'disk' => $disks[0] ?? 'backups',
            'filename' => $filename,
            'encrypted' => (string) config('backup.backup.password') !== '',
            'created_by' => $user?->id,
        ]);
    }
}
