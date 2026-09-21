<?php

namespace App\Notifications\Backups;

use App\Enums\NotificationCategory;
use App\Enums\RestoreScope;
use App\Models\Backup;
use App\Notifications\AdminNotification;

/**
 * A restore completed — every backup manager is told, because the site's
 * data just changed wholesale and whoever did it may not be the only one
 * who needs to know.
 */
class BackupRestoredAlert extends AdminNotification
{
    public function __construct(
        public Backup $backup,
        private RestoreScope $scope,
        private ?string $byName = null,
    ) {
        parent::__construct();
    }

    public static function category(): NotificationCategory
    {
        return NotificationCategory::Backup;
    }

    public function title(): string
    {
        return 'Backup #'.$this->backup->id.' restored ('.$this->scope->label().')';
    }

    public function message(): string
    {
        return 'Restored by '.($this->byName ?? 'the system').' from the '.$this->backup->type->label()
            .' backup taken '.$this->backup->completed_at?->format('d M Y, g:i A').'.';
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
