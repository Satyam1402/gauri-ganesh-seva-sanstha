<?php

namespace App\Interfaces;

use App\Models\Backup;
use App\Models\BackupLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface BackupRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters  type, status
     */
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function find(int $id): ?Backup;

    /**
     * Completed backups whose archive is expected on disk, newest first.
     *
     * @return Collection<int, Backup>
     */
    public function completed(): Collection;

    public function latestCompleted(): ?Backup;

    public function latestScheduled(): ?Backup;

    /**
     * Rows still queued or running (used to refuse concurrent restores).
     *
     * @return Collection<int, Backup>
     */
    public function inProgress(): Collection;

    /**
     * @param  array<string, mixed>  $filters  event
     */
    public function paginateLogs(array $filters = [], int $perPage = 30): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createLog(array $attributes): BackupLog;

    /**
     * @return array<string, int>  status value => count
     */
    public function countsByStatus(): array;
}
