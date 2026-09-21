<?php

namespace App\Support\Backups;

final class BackupRunResult
{
    /**
     * @param  list<string>  $disks  Disks the archive was written to.
     */
    private function __construct(
        public readonly bool $success,
        public readonly ?string $path = null,
        public readonly ?int $sizeBytes = null,
        public readonly array $disks = [],
        public readonly ?string $error = null,
    ) {}

    /**
     * @param  list<string>  $disks
     */
    public static function success(string $path, int $sizeBytes, array $disks): self
    {
        return new self(true, $path, $sizeBytes, $disks);
    }

    public static function failure(string $error): self
    {
        return new self(false, error: $error);
    }
}
