<?php

namespace App\Support\Backups;

use App\Contracts\BackupRunner;
use App\Models\Backup;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Runs spatie/laravel-backup's `backup:run` for one Backup row and reports
 * where the archive landed. Spatie writes `{backup name}/{filename}` to
 * every configured destination disk; we treat the first disk as primary.
 */
class SpatieBackupRunner implements BackupRunner
{
    public function run(Backup $backup): BackupRunResult
    {
        $path = config('backup.backup.name').'/'.$backup->filename;

        try {
            $exitCode = Artisan::call('backup:run', $backup->type->artisanOptions() + [
                '--filename' => $backup->filename,
                '--disable-notifications' => true,
            ]);
            $output = Artisan::output();
        } catch (Throwable $e) {
            return BackupRunResult::failure($e->getMessage());
        }

        $disks = array_values(array_filter(
            (array) config('backup.backup.destination.disks'),
            fn (string $disk) => Storage::disk($disk)->exists($path),
        ));

        if ($exitCode !== 0 || ! in_array($backup->disk, $disks, true)) {
            return BackupRunResult::failure($this->extractError($output) ?? 'backup:run exited with code '.$exitCode.' and no archive was written.');
        }

        return BackupRunResult::success($path, (int) Storage::disk($backup->disk)->size($path), $disks);
    }

    /**
     * Spatie prints "Backup failed because: …" on failure — keep that line
     * (the full console output may include paths and commands we do not
     * want in the panel).
     */
    private function extractError(string $output): ?string
    {
        // Console colour codes make the text unreadable in the panel.
        $output = preg_replace('/\e\[[0-9;]*m/', '', $output) ?? $output;

        if (preg_match('/Backup failed because:\s*(.+)/i', $output, $matches) !== 1) {
            $lines = array_values(array_filter(array_map('trim', explode("\n", $output))));

            return $lines === [] ? null : end($lines);
        }

        $reason = trim($matches[1]);

        // db-dumper appends the tool's stderr under "Error Output" — that is
        // the line an admin needs ("mysqldump: command not found", "Access
        // denied…"). Everything after it is a stack trace we do not want.
        if (preg_match('/Error Output\s*=+\s*(.+?)(?:\n\s*#\d+ |\z)/s', $output, $stderr) === 1) {
            $detail = trim(preg_replace('/\s+/', ' ', $stderr[1]) ?? '');

            if ($detail !== '' && $detail !== '.' && $detail !== '<no output>') {
                $reason .= ' '.rtrim($detail, ' .').'.';
            }
        }

        return $reason;
    }
}
