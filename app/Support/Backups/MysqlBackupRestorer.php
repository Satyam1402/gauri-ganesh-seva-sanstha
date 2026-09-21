<?php

namespace App\Support\Backups;

use App\Contracts\BackupRestorer;
use App\Enums\RestoreScope;
use App\Models\Backup;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Local\LocalFilesystemAdapter;
use RuntimeException;
use Symfony\Component\Process\Process;
use ZipArchive;

/**
 * Restores spatie/laravel-backup archives in this environment:
 *
 *  - Database: the `db-dumps/*.sql` entry is streamed into the `mysql`
 *    client (MySQL/MariaDB only). The password is passed through the
 *    MYSQL_PWD environment variable so it never appears in a process list.
 *  - Files: entries under storage/app/ are written back in place. Existing
 *    files are overwritten, files that are not in the archive are left
 *    alone — a restore never deletes uploads.
 *
 * Entry names are normalised (spatie on Windows stores backslashes) and
 * checked for path traversal before anything touches the filesystem.
 */
class MysqlBackupRestorer implements BackupRestorer
{
    private const FILES_PREFIXES = ['storage/app/public/', 'storage/app/private/'];

    public function supports(RestoreScope $scope): bool
    {
        if (! $scope->includesDatabase()) {
            return true;
        }

        $driver = (string) config('database.connections.'.config('database.default').'.driver');

        return in_array($driver, ['mysql', 'mariadb'], true);
    }

    public function inspect(Backup $backup): ArchiveInfo
    {
        [$zip, $cleanup] = $this->open($backup);

        try {
            $dumps = [];
            $files = 0;

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $this->normalise((string) $zip->getNameIndex($i));

                if ($name === '' || str_ends_with($name, '/')) {
                    continue;
                }

                if (str_starts_with($name, 'db-dumps/') && str_ends_with($name, '.sql')) {
                    $dumps[] = (string) $zip->getNameIndex($i);
                } elseif ($this->isRestorableFile($name)) {
                    $files++;
                }
            }

            // Reading a few bytes proves the password (if any) is right —
            // ZipArchive only fails at stream time for AES entries.
            $probe = $dumps[0] ?? ($zip->numFiles > 0 ? (string) $zip->getNameIndex(0) : null);

            if ($probe !== null) {
                $stream = $zip->getStream($probe);

                if ($stream === false) {
                    throw new RuntimeException('The archive could not be opened for reading — the BACKUP_ARCHIVE_PASSWORD may differ from the one used when it was created.');
                }

                fread($stream, 16);
                fclose($stream);
            }

            return new ArchiveInfo((bool) $backup->encrypted, $dumps, $files, $zip->numFiles);
        } finally {
            $zip->close();
            $cleanup();
        }
    }

    public function restore(Backup $backup, RestoreScope $scope): void
    {
        if (! $this->supports($scope)) {
            throw new RuntimeException('Database restore is only supported on MySQL/MariaDB connections.');
        }

        $info = $this->inspect($backup);

        if ($scope->includesDatabase() && ! $info->hasDatabase()) {
            throw new RuntimeException('This archive does not contain a database dump.');
        }

        if ($scope->includesFiles() && ! $info->hasFiles()) {
            throw new RuntimeException('This archive does not contain any uploaded files.');
        }

        [$zip, $cleanup] = $this->open($backup);
        $tempDir = rtrim((string) config('backup.backup.temporary_directory'), '/\\').DIRECTORY_SEPARATOR.'restore-'.uniqid();

        try {
            if ($scope->includesDatabase()) {
                $this->restoreDatabase($zip, $info->dumpEntries[0], $tempDir);
            }

            if ($scope->includesFiles()) {
                $this->restoreFiles($zip);
            }
        } finally {
            $zip->close();
            $cleanup();
            $this->removeDirectory($tempDir);
        }
    }

    private function restoreDatabase(ZipArchive $zip, string $entry, string $tempDir): void
    {
        if (! is_dir($tempDir) && ! mkdir($tempDir, 0700, true) && ! is_dir($tempDir)) {
            throw new RuntimeException('Could not create the temporary restore directory.');
        }

        $dumpPath = $tempDir.DIRECTORY_SEPARATOR.'restore.sql';
        $this->extractEntry($zip, $entry, $dumpPath);

        $connection = (array) config('database.connections.'.config('database.default'));
        $binaryDir = rtrim((string) config('backup.app.mysql_binary_path'), '/\\');
        $binary = $binaryDir === '' ? 'mysql' : $binaryDir.DIRECTORY_SEPARATOR.'mysql';

        $command = [
            $binary,
            '--host='.($connection['host'] ?? '127.0.0.1'),
            '--port='.($connection['port'] ?? '3306'),
            '--user='.($connection['username'] ?? 'root'),
            '--default-character-set='.($connection['charset'] ?? 'utf8mb4'),
        ];

        if (! empty($connection['unix_socket'])) {
            $command[] = '--socket='.$connection['unix_socket'];
        }

        $command[] = (string) $connection['database'];

        $input = fopen($dumpPath, 'rb');

        if ($input === false) {
            throw new RuntimeException('Could not read the extracted database dump.');
        }

        try {
            $process = new Process($command, base_path(), ['MYSQL_PWD' => (string) ($connection['password'] ?? '')]);
            $process->setInput($input);
            $process->setTimeout((float) config('backup.app.job_timeout', 3600));
            $process->run();

            if (! $process->isSuccessful()) {
                throw new RuntimeException('The mysql client reported an error: '.trim($process->getErrorOutput() ?: 'exit code '.$process->getExitCode()));
            }
        } finally {
            fclose($input);
        }
    }

    private function restoreFiles(ZipArchive $zip): void
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $raw = (string) $zip->getNameIndex($i);
            $name = $this->normalise($raw);

            if (! $this->isRestorableFile($name) || str_ends_with($name, '/')) {
                continue;
            }

            $this->extractEntry($zip, $raw, base_path($name));
        }
    }

    /**
     * Stream one entry to an absolute path, creating parent directories.
     */
    private function extractEntry(ZipArchive $zip, string $entry, string $target): void
    {
        $directory = dirname($target);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException("Could not create directory for {$target}.");
        }

        $source = $zip->getStream($entry);

        if ($source === false) {
            throw new RuntimeException("Could not read {$entry} from the archive.");
        }

        $destination = fopen($target, 'wb');

        if ($destination === false) {
            fclose($source);
            throw new RuntimeException("Could not write {$target}.");
        }

        stream_copy_to_stream($source, $destination);
        fclose($source);
        fclose($destination);
    }

    /**
     * Only uploads are restored — never code, docs or manifests — and only
     * paths that stay inside their prefix (no "..", no absolute paths).
     */
    private function isRestorableFile(string $name): bool
    {
        if (str_contains($name, '..') || str_starts_with($name, '/') || preg_match('/^[A-Za-z]:/', $name) === 1) {
            return false;
        }

        foreach (self::FILES_PREFIXES as $prefix) {
            if (str_starts_with($name, $prefix) && strlen($name) > strlen($prefix)) {
                return true;
            }
        }

        return false;
    }

    private function normalise(string $name): string
    {
        return ltrim(str_replace('\\', '/', $name), '/');
    }

    /**
     * Open the archive from the first disk that still has it. Non-local
     * disks are streamed to a temporary file first. Returns the ZipArchive
     * and a cleanup callback for that temp file.
     *
     * @return array{0: ZipArchive, 1: callable(): void}
     */
    private function open(Backup $backup): array
    {
        if ($backup->path === null) {
            throw new RuntimeException('This backup has no archive.');
        }

        $local = null;
        $cleanup = static function (): void {};

        foreach (array_unique(array_merge([$backup->disk], (array) $backup->disks)) as $diskName) {
            $disk = Storage::disk($diskName);

            if (! $disk->exists($backup->path)) {
                continue;
            }

            if ($disk->getAdapter() instanceof LocalFilesystemAdapter) {
                $local = $disk->path($backup->path);
                break;
            }

            $temp = tempnam(sys_get_temp_dir(), 'ggss-restore-');
            $stream = $disk->readStream($backup->path);

            if ($temp === false || $stream === false) {
                continue;
            }

            file_put_contents($temp, $stream);
            $local = $temp;
            $cleanup = static function () use ($temp): void {
                @unlink($temp);
            };
            break;
        }

        if ($local === null) {
            throw new RuntimeException('The backup archive no longer exists on any configured disk.');
        }

        $zip = new ZipArchive;

        if ($zip->open($local) !== true) {
            $cleanup();
            throw new RuntimeException('The backup archive is not a readable zip file.');
        }

        $password = (string) config('backup.backup.password');

        if ($backup->encrypted && $password === '') {
            $zip->close();
            $cleanup();
            throw new RuntimeException('This archive is encrypted but BACKUP_ARCHIVE_PASSWORD is not set.');
        }

        if ($password !== '') {
            $zip->setPassword($password);
        }

        return [$zip, $cleanup];
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory.DIRECTORY_SEPARATOR.$item;
            is_dir($path) ? $this->removeDirectory($path) : @unlink($path);
        }

        @rmdir($directory);
    }
}
