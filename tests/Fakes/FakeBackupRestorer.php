<?php

namespace Tests\Fakes;

use App\Contracts\BackupRestorer;
use App\Enums\RestoreScope;
use App\Models\Backup;
use App\Support\Backups\ArchiveInfo;
use RuntimeException;

/**
 * Never touches the database or the filesystem — records what a real
 * restorer would have been asked to do.
 */
class FakeBackupRestorer implements BackupRestorer
{
    public bool $supportsDatabase = true;

    public ?string $failWith = null;

    public ?string $inspectFailsWith = null;

    /** @var list<array{backup: int, scope: string}> */
    public array $restored = [];

    public function inspect(Backup $backup): ArchiveInfo
    {
        if ($this->inspectFailsWith !== null) {
            throw new RuntimeException($this->inspectFailsWith);
        }

        return new ArchiveInfo(
            (bool) $backup->encrypted,
            $backup->type->includesDatabase() ? ['db-dumps/mysql-test.sql'] : [],
            $backup->type->includesFiles() ? 12 : 0,
            15,
        );
    }

    public function supports(RestoreScope $scope): bool
    {
        return ! $scope->includesDatabase() || $this->supportsDatabase;
    }

    public function restore(Backup $backup, RestoreScope $scope): void
    {
        if ($this->failWith !== null) {
            throw new RuntimeException($this->failWith);
        }

        $this->restored[] = ['backup' => $backup->id, 'scope' => $scope->value];
    }
}
