<?php

namespace App\Notifications\Backups;

use App\Enums\NotificationCategory;
use App\Models\Backup;
use App\Notifications\AdminNotification;

class RestoreFailedAlert extends AdminNotification
{
    public function __construct(public Backup $backup, private string $reason)
    {
        parent::__construct();
    }

    public static function category(): NotificationCategory
    {
        return NotificationCategory::Backup;
    }

    public function title(): string
    {
        return 'Restore from backup #'.$this->backup->id.' failed';
    }

    public function message(): string
    {
        return $this->reason;
    }

    public function actionUrl(): ?string
    {
        return route('admin.backups.logs');
    }

    public function actionLabel(): string
    {
        return 'View Backup Log';
    }
}
