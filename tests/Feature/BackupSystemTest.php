<?php

namespace Tests\Feature;

use App\Contracts\BackupRestorer;
use App\Contracts\BackupRunner;
use App\Enums\BackupLogEvent;
use App\Enums\BackupStatus;
use App\Enums\BackupTrigger;
use App\Enums\BackupType;
use App\Enums\Role as RoleEnum;
use App\Jobs\RestoreBackupJob;
use App\Jobs\RunBackupJob;
use App\Models\Backup;
use App\Models\BackupLog;
use App\Models\User;
use App\Notifications\Backups\BackupFailedAlert;
use App\Services\BackupService;
use App\Support\Backups\SecretScrubber;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\Fakes\FakeBackupRestorer;
use Tests\Fakes\FakeBackupRunner;
use Tests\TestCase;

/**
 * Retention, reconciliation, scheduler commands and queue behaviour of
 * the backup system — nothing here touches a real database dump.
 */
class BackupSystemTest extends TestCase
{
    use RefreshDatabase;

    private FakeBackupRunner $runner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        Storage::fake('backups');
        config([
            'backup.backup.name' => 'ggss',
            'backup.backup.destination.disks' => ['backups'],
            'backup.backup.password' => 'test-archive-password',
            'backup.monitor_backups.0.disks' => ['backups'],
        ]);

        $this->runner = new FakeBackupRunner;
        $this->app->instance(BackupRunner::class, $this->runner);
        $this->app->instance(BackupRestorer::class, new FakeBackupRestorer);
    }

    private function service(): BackupService
    {
        return app(BackupService::class);
    }

    /**
     * A completed backup whose archive exists on the faked disk with the
     * given age (spatie's retention reads the file's modification time).
     */
    private function archivedBackup(int $daysOld, BackupType $type = BackupType::Database): Backup
    {
        $filename = $type->filePrefix().'-'.now()->subDays($daysOld)->format('Y-m-d-H-i-s').'-'.uniqid().'.zip';
        $path = 'ggss/'.$filename;
        Storage::disk('backups')->put($path, FakeBackupRunner::zipBytes());
        touch(Storage::disk('backups')->path($path), now()->subDays($daysOld)->getTimestamp());

        return Backup::create([
            'type' => $type->value,
            'status' => BackupStatus::Completed->value,
            'trigger' => BackupTrigger::Scheduled->value,
            'disk' => 'backups',
            'filename' => $filename,
            'path' => $path,
            'size_bytes' => Storage::disk('backups')->size($path),
            'encrypted' => true,
            'disks' => ['backups'],
            'completed_at' => now()->subDays($daysOld),
            'created_at' => now()->subDays($daysOld),
        ]);
    }

    // ── retention ─────────────────────────────────────────────────────────

    public function test_retention_cleanup_removes_out_of_policy_archives_but_never_the_newest_and_marks_rows_expired(): void
    {
        config(['backup.cleanup.default_strategy' => [
            'keep_all_backups_for_days' => 0,
            'keep_daily_backups_for_days' => 0,
            'keep_weekly_backups_for_weeks' => 0,
            'keep_monthly_backups_for_months' => 0,
            'keep_yearly_backups_for_years' => 0,
            'delete_oldest_backups_when_using_more_megabytes_than' => 5000,
        ]]);

        $oldest = $this->archivedBackup(40);
        $middle = $this->archivedBackup(20);
        $newest = $this->archivedBackup(1);

        $result = $this->service()->cleanup();

        $this->assertTrue($result['cleaned']);
        $this->assertNull($result['error']);
        $this->assertSame(2, $result['expired']);

        Storage::disk('backups')->assertExists($newest->path);
        Storage::disk('backups')->assertMissing($oldest->path);
        Storage::disk('backups')->assertMissing($middle->path);

        $this->assertSame(BackupStatus::Completed, $newest->fresh()->status);
        $this->assertSame(BackupStatus::Expired, $oldest->fresh()->status);
        $this->assertSame(BackupStatus::Expired, $middle->fresh()->status);

        $this->assertSame(2, BackupLog::where('event', BackupLogEvent::BackupExpired->value)->count());
        $this->assertDatabaseHas('backup_logs', ['event' => BackupLogEvent::CleanupRun->value]);
    }

    public function test_retention_keeps_everything_inside_the_keep_all_window(): void
    {
        config(['backup.cleanup.default_strategy.keep_all_backups_for_days' => 7]);

        $a = $this->archivedBackup(5);
        $b = $this->archivedBackup(2);
        $c = $this->archivedBackup(0);

        $result = $this->service()->cleanup();

        $this->assertSame(0, $result['expired']);
        foreach ([$a, $b, $c] as $backup) {
            Storage::disk('backups')->assertExists($backup->path);
            $this->assertSame(BackupStatus::Completed, $backup->fresh()->status);
        }
    }

    public function test_the_last_valid_backup_cannot_be_deleted_even_when_other_rows_exist_without_archives(): void
    {
        $service = $this->service();
        $missing = $this->archivedBackup(3);
        Storage::disk('backups')->delete($missing->path);
        $only = $this->archivedBackup(1);

        $this->assertTrue($service->isLastValidBackup($only));

        $this->expectExceptionMessage('only remaining valid backup');
        $service->delete($only);
    }

    public function test_reconcile_expires_rows_whose_archive_vanished_and_rescan_registers_orphan_archives(): void
    {
        $service = $this->service();
        $gone = $this->archivedBackup(2);
        Storage::disk('backups')->delete($gone->path);

        Storage::disk('backups')->put('ggss/db-2026-01-01-01-00-00-abcd.zip', FakeBackupRunner::zipBytes());
        Storage::disk('backups')->put('ggss/full-2026-01-02-01-00-00-abcd.zip', FakeBackupRunner::zipBytes());
        Storage::disk('backups')->put('ggss/notes.txt', 'ignored');

        $this->assertSame(1, $service->reconcile());
        $this->assertSame(BackupStatus::Expired, $gone->fresh()->status);

        $this->assertSame(2, $service->rescan());
        $this->assertSame(0, $service->rescan(), 'Rescan is idempotent.');

        $registered = Backup::where('filename', 'full-2026-01-02-01-00-00-abcd.zip')->firstOrFail();
        $this->assertSame(BackupStatus::Completed, $registered->status);
        $this->assertSame(BackupType::Full, $registered->type);
        $this->assertSame('ggss/full-2026-01-02-01-00-00-abcd.zip', $registered->path);
        $this->assertSame(BackupType::Database, Backup::where('filename', 'db-2026-01-01-01-00-00-abcd.zip')->firstOrFail()->type);
    }

    // ── scheduler & commands ──────────────────────────────────────────────

    public function test_the_scheduled_command_runs_inline_and_records_a_scheduled_backup(): void
    {
        $this->artisan('backups:run', ['type' => 'files'])->assertSuccessful();

        $backup = Backup::firstOrFail();
        $this->assertSame(BackupStatus::Completed, $backup->status);
        $this->assertSame(BackupType::Files, $backup->type);
        $this->assertSame(BackupTrigger::Scheduled, $backup->trigger);
        $this->assertNull($backup->created_by);
        Storage::disk('backups')->assertExists($backup->path);

        $this->artisan('backups:run', ['type' => 'nonsense'])->assertFailed();
    }

    public function test_the_scheduled_command_exits_non_zero_when_the_backup_fails(): void
    {
        Notification::fake();
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(RoleEnum::SuperAdmin->value);
        $this->runner->shouldFail = true;

        $this->artisan('backups:run', ['type' => 'database'])->assertFailed();

        $this->assertSame(BackupStatus::Failed, Backup::firstOrFail()->status);
        Notification::assertSentTo($superAdmin, BackupFailedAlert::class);
    }

    public function test_the_clean_command_and_queue_flag_work(): void
    {
        $this->archivedBackup(1);

        $this->artisan('backups:clean')->assertSuccessful();
        $this->assertDatabaseHas('backup_logs', ['event' => BackupLogEvent::CleanupRun->value]);

        Queue::fake();
        $this->artisan('backups:run', ['type' => 'database', '--queue' => true])->assertSuccessful();
        Queue::assertPushed(RunBackupJob::class);
        $this->assertSame(BackupStatus::Pending, Backup::latest('id')->first()->status);
    }

    public function test_backups_are_scheduled_daily_and_weekly_with_cleanup(): void
    {
        $commands = collect(app(Schedule::class)->events())
            ->map(fn ($event) => trim(preg_replace('/^.*artisan[^ ]*\s*/', '', $event->command ?? '')))
            ->filter(fn (string $command) => str_starts_with($command, 'backups:'))
            ->values();

        $this->assertContains('backups:run database', $commands->all());
        $this->assertContains('backups:run files', $commands->all());
        $this->assertContains('backups:run full', $commands->all());
        $this->assertContains('backups:clean', $commands->all());
    }

    // ── queue behaviour ───────────────────────────────────────────────────

    public function test_manual_backups_are_queued_jobs_with_a_long_timeout_and_no_automatic_retry(): void
    {
        Queue::fake();
        config(['backup.app.job_timeout' => 1234]);

        $backup = $this->service()->queue(BackupType::Database, BackupTrigger::Manual);

        $this->assertSame(BackupStatus::Pending, $backup->status);
        Queue::assertPushed(RunBackupJob::class, function (RunBackupJob $job) use ($backup) {
            return $job->backupId === $backup->id && $job->timeout === 1234 && $job->tries === 1;
        });
        $this->assertInstanceOf(ShouldQueue::class, new RestoreBackupJob(1, 'database'));
    }

    public function test_a_job_killed_by_the_worker_leaves_the_backup_marked_failed_not_running(): void
    {
        Notification::fake();
        $backup = Backup::create(['type' => 'database', 'status' => 'running', 'trigger' => 'manual', 'disk' => 'backups', 'filename' => 'db-x.zip', 'started_at' => now()]);

        (new RunBackupJob($backup->id))->failed(new RuntimeException('Job exceeded the timeout'));

        $backup->refresh();
        $this->assertSame(BackupStatus::Failed, $backup->status);
        $this->assertStringContainsString('timeout', $backup->failure_reason);
        $this->assertDatabaseHas('backup_logs', ['backup_id' => $backup->id, 'event' => BackupLogEvent::BackupFailed->value]);
    }

    public function test_stale_in_progress_rows_are_failed_by_cleanup_only_after_the_job_timeout(): void
    {
        Notification::fake();
        config(['backup.app.job_timeout' => 600]);

        $fresh = Backup::create(['type' => 'database', 'status' => 'running', 'trigger' => 'manual', 'disk' => 'backups', 'filename' => 'a.zip', 'started_at' => now()->subMinutes(2)]);
        $stale = Backup::create(['type' => 'database', 'status' => 'pending', 'trigger' => 'manual', 'disk' => 'backups', 'filename' => 'b.zip']);
        $stale->forceFill(['created_at' => now()->subHours(3)])->save();

        $this->service()->cleanup();

        $this->assertSame(BackupStatus::Running, $fresh->fresh()->status);
        $this->assertSame(BackupStatus::Failed, $stale->fresh()->status);
        $this->assertStringContainsString('never finished', $stale->fresh()->failure_reason);
    }

    public function test_the_secret_scrubber_removes_configured_secrets(): void
    {
        config(['database.connections.sqlite.password' => 'db-secret-1', 'filesystems.disks.backups_s3.secret' => 'S3SECRETKEY']);

        $scrubbed = app(SecretScrubber::class)->scrub('mysqldump --password=db-secret-1 failed; s3 key S3SECRETKEY rejected; archive test-archive-password');

        $this->assertStringNotContainsString('db-secret-1', $scrubbed);
        $this->assertStringNotContainsString('S3SECRETKEY', $scrubbed);
        $this->assertStringNotContainsString('test-archive-password', $scrubbed);
        $this->assertStringContainsString('mysqldump', $scrubbed);
    }
}
