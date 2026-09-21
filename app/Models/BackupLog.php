<?php

namespace App\Models;

use App\Enums\BackupLogEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only audit entry for a backup/restore operation. Written only by
 * BackupService::log(); never updated.
 */
class BackupLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'backup_id',
        'event',
        'user_id',
        'ip_address',
        'message',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'event' => BackupLogEvent::class,
            'context' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function backup(): BelongsTo
    {
        return $this->belongsTo(Backup::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
