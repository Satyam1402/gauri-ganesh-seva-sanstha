<?php

namespace App\Repositories;

use App\Enums\BackupLogEvent;
use App\Enums\BackupStatus;
use App\Enums\BackupTrigger;
use App\Enums\BackupType;
use App\Interfaces\BackupRepositoryInterface;
use App\Models\Backup;
use App\Models\BackupLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class BackupRepository implements BackupRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Backup::query()->with('creator')->latest('id');

        if ($type = BackupType::tryFrom((string) ($filters['type'] ?? ''))) {
            $query->where('type', $type->value);
        }

        if ($status = BackupStatus::tryFrom((string) ($filters['status'] ?? ''))) {
            $query->where('status', $status->value);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function find(int $id): ?Backup
    {
        return Backup::query()->find($id);
    }

    public function completed(): Collection
    {
        return Backup::query()->completed()->latest('completed_at')->get();
    }

    public function latestCompleted(): ?Backup
    {
        return Backup::query()->completed()->latest('completed_at')->first();
    }

    public function latestScheduled(): ?Backup
    {
        return Backup::query()->where('trigger', BackupTrigger::Scheduled->value)->latest('id')->first();
    }

    public function inProgress(): Collection
    {
        return Backup::query()
            ->whereIn('status', [BackupStatus::Pending->value, BackupStatus::Running->value])
            ->get();
    }

    public function paginateLogs(array $filters = [], int $perPage = 30): LengthAwarePaginator
    {
        $query = BackupLog::query()->with(['user', 'backup'])->latest('id');

        if ($event = BackupLogEvent::tryFrom((string) ($filters['event'] ?? ''))) {
            $query->where('event', $event->value);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function createLog(array $attributes): BackupLog
    {
        return BackupLog::query()->create($attributes);
    }

    public function countsByStatus(): array
    {
        return Backup::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count) => (int) $count)
            ->all();
    }
}
