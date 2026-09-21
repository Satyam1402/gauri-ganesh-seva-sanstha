<?php

namespace App\Models;

use App\Enums\BackupStatus;
use App\Enums\BackupTrigger;
use App\Enums\BackupType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One backup run (queued, running, completed, failed, or an archive that
 * has since been deleted/expired). The archive itself lives on the private
 * "backups" disk at {path}; this row is the panel's and audit trail's view.
 */
class Backup extends Model
{
    protected $fillable = [
        'type',
        'status',
        'trigger',
        'disk',
        'filename',
        'path',
        'size_bytes',
        'encrypted',
        'disks',
        'started_at',
        'completed_at',
        'failure_reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => BackupType::class,
            'status' => BackupStatus::class,
            'trigger' => BackupTrigger::class,
            'encrypted' => 'boolean',
            'disks' => 'array',
            'size_bytes' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(BackupLog::class);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', BackupStatus::Completed->value);
    }

    public function isCompleted(): bool
    {
        return $this->status === BackupStatus::Completed;
    }

    public function isFailed(): bool
    {
        return $this->status === BackupStatus::Failed;
    }

    public function isInProgress(): bool
    {
        return in_array($this->status, [BackupStatus::Pending, BackupStatus::Running], true);
    }

    /**
     * Human-readable size, e.g. "12.4 MB".
     */
    public function sizeForHumans(): ?string
    {
        if ($this->size_bytes === null) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = (float) $this->size_bytes;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return ($unit === 0 ? (string) (int) $size : number_format($size, 1)).' '.$units[$unit];
    }

    /**
     * Seconds the run took, when both timestamps are known.
     */
    public function durationSeconds(): ?int
    {
        if ($this->started_at === null || $this->completed_at === null) {
            return null;
        }

        return max(0, $this->completed_at->diffInSeconds($this->started_at));
    }
}
