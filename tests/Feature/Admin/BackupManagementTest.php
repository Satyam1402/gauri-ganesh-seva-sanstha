<?php

namespace Tests\Feature\Admin;

use App\Contracts\BackupRestorer;
use App\Contracts\BackupRunner;
use App\Enums\BackupLogEvent;
use App\Enums\BackupStatus;
use App\Enums\BackupTrigger;
use App\Enums\BackupType;
use App\Enums\Role as RoleEnum;
use App\Models\Backup;
use App\Models\BackupLog;
use App\Models\User;
use App\Notifications\Backups\BackupFailedAlert;
use App\Notifications\Backups\BackupRestoredAlert;
use App\Notifications\Backups\RestoreFailedAlert;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Fakes\FakeBackupRestorer;
use Tests\Fakes\FakeBackupRunner;
use Tests\TestCase;

class BackupManagementTest extends TestCase
{
    use RefreshDatabase;

    private FakeBackupRunner $runner;

    private FakeBackupRestorer $restorer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        Storage::fake('backups');
        config([
            'backup.backup.name' => 'ggss',
            'backup.backup.destination.disks' => ['backups'],
            'backup.backup.password' => 'test-archive-password',
        ]);

        $this->runner = new FakeBackupRunner;
        $this->restorer = new FakeBackupRestorer;
        $this->app->instance(BackupRunner::class, $this->runner);
        $this->app->instance(BackupRestorer::class, $this->restorer);
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['password' => 'Admin@12345']);
        $admin->assignRole(RoleEnum::Admin->value);

        return $admin->fresh();
    }

    private function contentManager(): User
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::ContentManager->value);

        return $user->fresh();
    }

    /**
     * A completed backup whose archive really exists on the faked disk.
     */
    private function completedBackup(BackupType $type = BackupType::Database, array $overrides = []): Backup
    {
        $filename = $type->filePrefix().'-'.now()->format('Y-m-d-H-i-s').'-'.uniqid().'.zip';
        $path = 'ggss/'.$filename;
        Storage::disk('backups')->put($path, FakeBackupRunner::zipBytes($type->includesDatabase(), $type->includesFiles()));

        return Backup::create(array_merge([
            'type' => $type->value,
            'status' => BackupStatus::Completed->value,
            'trigger' => BackupTrigger::Manual->value,
            'disk' => 'backups',
            'filename' => $filename,
            'path' => $path,
            'size_bytes' => Storage::disk('backups')->size($path),
            'encrypted' => true,
            'disks' => ['backups'],
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
        ], $overrides));
    }

    // ── authorization ─────────────────────────────────────────────────────

    public function test_guests_and_users_without_manage_backups_cannot_reach_any_backup_route(): void
    {
        $backup = $this->completedBackup();

        $this->get(route('admin.backups.index'))->assertRedirect(route('login'));
        $this->get(route('admin.backups.download', $backup))->assertRedirect(route('login'));

        $editor = $this->contentManager();

        $this->actingAs($editor)->get(route('admin.backups.index'))->assertForbidden();
        $this->actingAs($editor)->get(route('admin.backups.logs'))->assertForbidden();
        $this->actingAs($editor)->post(route('admin.backups.store'), ['type' => 'database'])->assertForbidden();
        $this->actingAs($editor)->get(route('admin.backups.download', $backup))->assertForbidden();
        $this->actingAs($editor)->delete(route('admin.backups.destroy', $backup))->assertForbidden();
        $this->actingAs($editor)->get(route('admin.backups.restore.confirm', $backup))->assertForbidden();
        $this->actingAs($editor)->post(route('admin.backups.restore', $backup), ['scope' => 'database', 'confirmation' => 'RESTORE', 'current_password' => 'password', 'acknowledge' => 1])->assertForbidden();

        $this->assertSame(0, Backup::where('status', BackupStatus::Deleted->value)->count());
        $this->assertSame([], $this->restorer->restored);
        $this->assertSame(0, BackupLog::count());
    }

    public function test_the_backups_page_lists_history_with_type_status_size_date_and_storage(): void
    {
        $completed = $this->completedBackup(BackupType::Full);
        Backup::create(['type' => 'database', 'status' => 'failed', 'trigger' => 'scheduled', 'disk' => 'backups', 'filename' => 'db-x.zip', 'failure_reason' => 'mysqldump not found', 'completed_at' => now()]);

        $response = $this->actingAs($this->admin())->get(route('admin.backups.index'));

        $response->assertOk();
        $response->assertSee($completed->filename);
        $response->assertSee('Full (database + files)');
        $response->assertSee('Completed');
        $response->assertSee('Failed');
        $response->assertSee('mysqldump not found');
        $response->assertSee($completed->sizeForHumans());
        $response->assertSee('backups');
        $response->assertSee('AES-256 on');
        $response->assertSee('Retry');
    }

    public function test_status_and_type_filters_scope_the_list(): void
    {
        $db = $this->completedBackup(BackupType::Database);
        $files = $this->completedBackup(BackupType::Files);
        $failed = Backup::create(['type' => 'database', 'status' => 'failed', 'trigger' => 'manual', 'disk' => 'backups', 'filename' => 'db-failed.zip']);

        $admin = $this->admin();

        $onlyFiles = $this->actingAs($admin)->get(route('admin.backups.index', ['type' => 'files']));
        $this->assertSame([$files->id], $onlyFiles->viewData('backups')->pluck('id')->all());

        $onlyFailed = $this->actingAs($admin)->get(route('admin.backups.index', ['status' => 'failed']));
        $this->assertSame([$failed->id], $onlyFailed->viewData('backups')->pluck('id')->all());

        $this->assertNotContains($db->id, $onlyFiles->viewData('backups')->pluck('id')->all());
    }

    // ── creating backups ──────────────────────────────────────────────────

    public function test_an_admin_can_create_a_backup_which_runs_on_the_queue_and_completes(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.backups.store'), ['type' => 'full'])
            ->assertRedirect(route('admin.backups.index'))
            ->assertSessionHas('status');

        $backup = Backup::firstOrFail();

        $this->assertSame(BackupStatus::Completed, $backup->status);
        $this->assertSame(BackupType::Full, $backup->type);
        $this->assertSame(BackupTrigger::Manual, $backup->trigger);
        $this->assertSame($admin->id, $backup->created_by);
        $this->assertTrue($backup->encrypted);
        $this->assertSame('ggss/'.$backup->filename, $backup->path);
        $this->assertGreaterThan(0, $backup->size_bytes);
        $this->assertNotNull($backup->started_at);
        $this->assertNotNull($backup->completed_at);
        Storage::disk('backups')->assertExists($backup->path);
        $this->assertStringStartsWith('full-', $backup->filename);

        $this->assertSame(
            [BackupLogEvent::BackupQueued, BackupLogEvent::BackupCompleted],
            BackupLog::orderBy('id')->pluck('event')->all(),
        );
        $this->assertSame($admin->id, BackupLog::first()->user_id);
        $this->assertNotNull(BackupLog::first()->ip_address);
    }

    public function test_an_invalid_backup_type_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.backups.store'), ['type' => 'everything'])
            ->assertSessionHasErrors('type');

        $this->assertSame(0, Backup::count());
    }

    public function test_a_failed_backup_is_recorded_with_a_scrubbed_reason_logged_and_alerted(): void
    {
        Notification::fake();
        config(['database.connections.sqlite.password' => 'secret123']);

        $this->runner->shouldFail = true;
        $admin = $this->admin();
        $viewer = User::factory()->create();
        $viewer->assignRole(RoleEnum::Viewer->value);

        $this->actingAs($admin)->post(route('admin.backups.store'), ['type' => 'database'])->assertRedirect();

        $backup = Backup::firstOrFail();

        $this->assertSame(BackupStatus::Failed, $backup->status);
        $this->assertStringContainsString('Access denied', $backup->failure_reason);
        $this->assertStringNotContainsString('secret123', $backup->failure_reason);
        $this->assertStringNotContainsString('test-archive-password', $backup->failure_reason);
        $this->assertNull($backup->path);

        $this->assertDatabaseHas('backup_logs', ['backup_id' => $backup->id, 'event' => BackupLogEvent::BackupFailed->value]);
        $this->assertStringNotContainsString('secret123', json_encode(BackupLog::where('event', 'backup_failed')->first()->context));

        Notification::assertSentTo($admin, BackupFailedAlert::class, fn (BackupFailedAlert $n) => $n->backup->is($backup));
        Notification::assertNotSentTo($viewer, BackupFailedAlert::class);

        // The panel shows the failure honestly and offers a retry.
        $page = $this->actingAs($admin)->get(route('admin.backups.index'));
        $page->assertSee('Failed');
        $page->assertSee('Access denied');
        $page->assertDontSee('secret123');

        $this->runner->shouldFail = false;
        $this->actingAs($admin)->post(route('admin.backups.retry', $backup))->assertRedirect();

        $this->assertSame(BackupStatus::Failed, $backup->fresh()->status, 'The failed run stays on record.');
        $this->assertSame(BackupStatus::Completed, Backup::latest('id')->first()->status);
    }

    // ── download & delete ─────────────────────────────────────────────────

    public function test_an_authorised_admin_can_download_an_archive_and_the_download_is_logged(): void
    {
        $admin = $this->admin();
        $backup = $this->completedBackup();

        $response = $this->actingAs($admin)->get(route('admin.backups.download', $backup));

        $response->assertOk();
        $response->assertDownload($backup->filename);
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));

        $log = BackupLog::where('event', BackupLogEvent::BackupDownloaded->value)->firstOrFail();
        $this->assertSame($backup->id, $log->backup_id);
        $this->assertSame($admin->id, $log->user_id);
    }

    public function test_downloading_a_backup_whose_archive_is_gone_fails_gracefully(): void
    {
        $backup = $this->completedBackup();
        Storage::disk('backups')->delete($backup->path);

        $this->actingAs($this->admin())
            ->from(route('admin.backups.index'))
            ->get(route('admin.backups.download', $backup))
            ->assertRedirect(route('admin.backups.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('backup_logs', ['event' => BackupLogEvent::BackupDownloaded->value]);
    }

    public function test_backups_are_never_reachable_through_the_public_storage_url(): void
    {
        $backup = $this->completedBackup();

        $this->assertFalse(Storage::disk('public')->exists('backups/'.$backup->filename));
        $this->assertStringStartsWith(storage_path('app/backups'), config('filesystems.disks.backups.root'));
        $this->assertFalse((bool) config('filesystems.disks.backups.serve'));
        $this->assertArrayNotHasKey('url', config('filesystems.disks.backups'));
    }

    public function test_deleting_a_backup_removes_the_archive_but_the_last_valid_backup_is_protected(): void
    {
        $admin = $this->admin();
        $older = $this->completedBackup();
        $newer = $this->completedBackup();

        $this->actingAs($admin)->delete(route('admin.backups.destroy', $older))->assertRedirect()->assertSessionHas('status');

        Storage::disk('backups')->assertMissing($older->path);
        $this->assertSame(BackupStatus::Deleted, $older->fresh()->status);
        $this->assertDatabaseHas('backup_logs', ['backup_id' => $older->id, 'event' => BackupLogEvent::BackupDeleted->value, 'user_id' => $admin->id]);

        $this->actingAs($admin)->delete(route('admin.backups.destroy', $newer))->assertRedirect()->assertSessionHas('error');

        Storage::disk('backups')->assertExists($newer->path);
        $this->assertSame(BackupStatus::Completed, $newer->fresh()->status);
    }

    // ── restore ───────────────────────────────────────────────────────────

    public function test_the_restore_confirmation_page_validates_the_archive_and_shows_warnings(): void
    {
        $backup = $this->completedBackup(BackupType::Full);

        $response = $this->actingAs($this->admin())->get(route('admin.backups.restore.confirm', $backup));

        $response->assertOk();
        $response->assertSee('overwrites live data');
        $response->assertSee('Archive validated');
        $response->assertSee('decrypted successfully');
        $response->assertSee('Type RESTORE to confirm');
        $response->assertSee('Your current password');
        $response->assertSee('Database and uploaded files');
        $this->assertSame(['database', 'files', 'both'], array_keys($response->viewData('scopes')));
    }

    public function test_an_invalid_archive_cannot_reach_the_restore_form(): void
    {
        $this->restorer->inspectFailsWith = 'The archive could not be opened for reading — wrong password.';
        $backup = $this->completedBackup();

        $this->actingAs($this->admin())
            ->get(route('admin.backups.restore.confirm', $backup))
            ->assertRedirect(route('admin.backups.index'))
            ->assertSessionHas('error');
    }

    public function test_restore_requires_the_typed_confirmation_the_current_password_and_an_acknowledgement(): void
    {
        $admin = $this->admin();
        $backup = $this->completedBackup();

        $this->actingAs($admin)->post(route('admin.backups.restore', $backup), [
            'scope' => 'database', 'confirmation' => 'restore', 'current_password' => 'wrong', 'acknowledge' => 1,
        ])->assertSessionHasErrors(['confirmation', 'current_password']);

        $this->actingAs($admin)->post(route('admin.backups.restore', $backup), [
            'scope' => 'database', 'confirmation' => 'RESTORE', 'current_password' => 'Admin@12345',
        ])->assertSessionHasErrors(['acknowledge']);

        $this->assertSame([], $this->restorer->restored);
        $this->assertDatabaseMissing('backup_logs', ['event' => BackupLogEvent::RestoreInitiated->value]);
    }

    public function test_a_confirmed_restore_takes_a_safety_backup_first_then_restores_logs_and_notifies(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $backup = $this->completedBackup(BackupType::Database);

        $this->actingAs($admin)->post(route('admin.backups.restore', $backup), [
            'scope' => 'database', 'confirmation' => 'RESTORE', 'current_password' => 'Admin@12345', 'acknowledge' => 1,
        ])->assertRedirect(route('admin.backups.logs'))->assertSessionHas('status');

        $this->assertSame([['backup' => $backup->id, 'scope' => 'database']], $this->restorer->restored);

        $safety = Backup::where('trigger', BackupTrigger::PreRestore->value)->firstOrFail();
        $this->assertSame(BackupStatus::Completed, $safety->status);
        $this->assertSame(BackupType::Database, $safety->type);
        $this->assertTrue($safety->created_at->gte($backup->created_at));

        $events = BackupLog::where('backup_id', $backup->id)->orderBy('id')->pluck('event')->all();
        $this->assertSame([BackupLogEvent::RestoreInitiated, BackupLogEvent::RestoreCompleted], $events);
        $this->assertSame($admin->id, BackupLog::where('event', 'restore_completed')->first()->user_id);
        $this->assertSame($safety->id, BackupLog::where('event', 'restore_completed')->first()->context['safety_backup_id']);

        Notification::assertSentTo($admin, BackupRestoredAlert::class);
    }

    public function test_a_failed_restore_is_logged_and_alerted_without_technical_leakage(): void
    {
        Notification::fake();
        config(['database.connections.sqlite.password' => 'secret123']);
        $this->restorer->failWith = 'The mysql client reported an error: access denied (password secret123)';
        $admin = $this->admin();
        $backup = $this->completedBackup();

        $this->actingAs($admin)->post(route('admin.backups.restore', $backup), [
            'scope' => 'database', 'confirmation' => 'RESTORE', 'current_password' => 'Admin@12345', 'acknowledge' => 1,
        ])->assertRedirect();

        $log = BackupLog::where('event', BackupLogEvent::RestoreFailed->value)->firstOrFail();
        $this->assertStringContainsString('access denied', $log->context['reason']);
        $this->assertStringNotContainsString('secret123', $log->context['reason']);

        Notification::assertSentTo($admin, RestoreFailedAlert::class);
        Notification::assertNotSentTo($admin, BackupRestoredAlert::class);
    }

    public function test_a_scope_the_archive_cannot_serve_is_refused(): void
    {
        $admin = $this->admin();
        $filesOnly = $this->completedBackup(BackupType::Files);

        $this->actingAs($admin)->post(route('admin.backups.restore', $filesOnly), [
            'scope' => 'database', 'confirmation' => 'RESTORE', 'current_password' => 'Admin@12345', 'acknowledge' => 1,
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertSame([], $this->restorer->restored);

        $this->restorer->supportsDatabase = false;
        $page = $this->actingAs($admin)->get(route('admin.backups.restore.confirm', $this->completedBackup(BackupType::Database)));
        $page->assertOk();
        $page->assertSee('Nothing in this archive can be restored');
        $this->assertSame([], $page->viewData('scopes'));
    }

    // ── audit log page ────────────────────────────────────────────────────

    public function test_the_audit_log_page_lists_events_with_actor_and_ip(): void
    {
        $admin = $this->admin();
        $backup = $this->completedBackup();

        $this->actingAs($admin)->get(route('admin.backups.download', $backup));
        $this->actingAs($admin)->delete(route('admin.backups.destroy', $this->completedBackup()));

        $response = $this->actingAs($admin)->get(route('admin.backups.logs'));

        $response->assertOk();
        $response->assertSee('Backup downloaded');
        $response->assertSee('Backup deleted');
        $response->assertSee($admin->name);
        $response->assertSee('127.0.0.1');

        $filtered = $this->actingAs($admin)->get(route('admin.backups.logs', ['event' => 'backup_deleted']));
        $this->assertSame(['backup_deleted'], $filtered->viewData('logs')->pluck('event.value')->unique()->values()->all());
    }
}
