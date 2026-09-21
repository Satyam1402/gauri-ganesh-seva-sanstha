<?php

namespace Tests\Fakes;

use App\Contracts\BackupRunner;
use App\Models\Backup;
use App\Support\Backups\BackupRunResult;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Stands in for spatie/laravel-backup in the test suite: writes a small
 * real zip to the (faked) backups disk, or fails with a configurable error.
 */
class FakeBackupRunner implements BackupRunner
{
    public bool $shouldFail = false;

    public string $error = 'mysqldump: Got error: 1045: Access denied for user root (using password: secret123)';

    public int $runs = 0;

    public function run(Backup $backup): BackupRunResult
    {
        $this->runs++;

        if ($this->shouldFail) {
            return BackupRunResult::failure($this->error);
        }

        $path = config('backup.backup.name').'/'.$backup->filename;
        Storage::disk($backup->disk)->put($path, self::zipBytes($backup->type->includesDatabase(), $backup->type->includesFiles()));

        return BackupRunResult::success($path, (int) Storage::disk($backup->disk)->size($path), [$backup->disk]);
    }

    /**
     * A genuine zip so anything that opens the archive works.
     */
    public static function zipBytes(bool $withDatabase = true, bool $withFiles = true): string
    {
        $temp = tempnam(sys_get_temp_dir(), 'fake-backup-');
        $zip = new ZipArchive;
        $zip->open($temp, ZipArchive::OVERWRITE);

        if ($withDatabase) {
            $zip->addFromString('db-dumps/mysql-test.sql', "-- fake dump\nSELECT 1;\n");
        }

        if ($withFiles) {
            $zip->addFromString('storage/app/public/sample.txt', 'hello');
        }

        $zip->addFromString('.env.example', 'APP_NAME=test');
        $zip->close();

        $bytes = (string) file_get_contents($temp);
        @unlink($temp);

        return $bytes;
    }
}
