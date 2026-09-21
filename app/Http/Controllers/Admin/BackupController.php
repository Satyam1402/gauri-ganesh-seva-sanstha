<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BackupLogEvent;
use App\Enums\BackupStatus;
use App\Enums\BackupTrigger;
use App\Enums\BackupType;
use App\Enums\RestoreScope;
use App\Exceptions\BackupOperationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RestoreBackupRequest;
use App\Http\Requests\Admin\StoreBackupRequest;
use App\Interfaces\BackupRepositoryInterface;
use App\Models\Backup;
use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function __construct(
        private BackupRepositoryInterface $backups,
        private BackupService $backupService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Backup::class);

        $filters = $request->only(['type', 'status']);

        return view('admin.backups.index', [
            'backups' => $this->backups->paginate($filters),
            'overview' => $this->backupService->overview(),
            'types' => BackupType::options(),
            'statuses' => BackupStatus::options(),
            'filters' => $filters,
        ]);
    }

    public function store(StoreBackupRequest $request): RedirectResponse
    {
        $this->authorize('create', Backup::class);

        $type = BackupType::from($request->validated('type'));
        $this->backupService->queue($type, BackupTrigger::Manual, $request->user(), $request->ip());

        return redirect()->route('admin.backups.index')
            ->with('status', $type->label().' backup queued. It will appear as completed once the worker finishes it.');
    }

    public function retry(Request $request, Backup $backup): RedirectResponse
    {
        $this->authorize('create', Backup::class);

        try {
            $this->backupService->retry($backup, $request->user(), $request->ip());
        } catch (BackupOperationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'A new '.$backup->type->label().' backup has been queued.');
    }

    public function download(Request $request, Backup $backup): StreamedResponse|RedirectResponse
    {
        $this->authorize('download', $backup);

        try {
            return $this->backupService->download($backup, $request->user(), $request->ip());
        } catch (BackupOperationException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(Request $request, Backup $backup): RedirectResponse
    {
        $this->authorize('delete', $backup);

        try {
            $this->backupService->delete($backup, $request->user(), $request->ip());
        } catch (BackupOperationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Backup deleted.');
    }

    public function cleanup(Request $request): RedirectResponse
    {
        $this->authorize('delete', Backup::class);

        $result = $this->backupService->cleanup($request->user());

        if ($result['error'] !== null) {
            return back()->with('error', 'Retention cleanup failed: '.$result['error']);
        }

        return back()->with('status', "Retention policy applied; {$result['expired']} backup(s) marked expired.");
    }

    /**
     * Confirmation step: validates the archive and shows what a restore
     * would do before anything is queued.
     */
    public function confirmRestore(Backup $backup): View|RedirectResponse
    {
        $this->authorize('restore', $backup);

        try {
            $info = $this->backupService->inspectForRestore($backup);
        } catch (BackupOperationException $e) {
            return redirect()->route('admin.backups.index')->with('error', $e->getMessage());
        }

        return view('admin.backups.restore', [
            'backup' => $backup,
            'info' => $info,
            'scopes' => $this->backupService->restoreScopesFor($backup, $info),
            'confirmation' => RestoreBackupRequest::CONFIRMATION,
            'databaseRestoreSupported' => $this->backupService->supportsDatabaseRestore(),
        ]);
    }

    public function restore(RestoreBackupRequest $request, Backup $backup): RedirectResponse
    {
        $this->authorize('restore', $backup);

        try {
            $this->backupService->requestRestore(
                $backup,
                RestoreScope::from($request->validated('scope')),
                $request->user(),
                $request->ip(),
            );
        } catch (BackupOperationException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.backups.logs')
            ->with('status', 'Restore queued. A safety backup is taken first; watch the log below and your notifications for the outcome.');
    }

    public function logs(Request $request): View
    {
        $this->authorize('viewAny', Backup::class);

        $filters = $request->only(['event']);

        return view('admin.backups.logs', [
            'logs' => $this->backups->paginateLogs($filters),
            'events' => BackupLogEvent::options(),
            'filters' => $filters,
        ]);
    }
}
