<?php

namespace App\Notifications\Backups;

use App\Enums\NotificationCategory;
use App\Models\Backup;
use App\Notifications\AdminNotification;

/**
 * A backup run failed. The reason has already been scrubbed of secrets by
 * BackupService; the panel link lets the admin retry.
 */
class BackupFailedAlert extends AdminNotification
{
    public function __construct(public Backup $backup)
    {
        parent::__construct();
    }

    public static function category(): NotificationCategory
    {
        return NotificationCategory::Backup;
    }

    public function title(): string
    {
        return $this->backup->type->label().' backup failed ('.$this->backup->trigger->label().')';
    }

    public function message(): string
    {
        return $this->backup->failure_reason ?: 'No archive was produced. Check the server log for details.';
    }

    public function actionUrl(): ?string
    {
        return route('admin.backups.index');
    }

    public function actionLabel(): string
    {
        return 'Open Backups';
    }
}
