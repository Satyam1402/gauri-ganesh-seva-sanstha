<?php

namespace App\Policies;

use App\Models\Backup;
use App\Models\User;

/**
 * Every backup operation — including downloads and restores — requires
 * "manage backups". Restore additionally re-verifies the admin's password
 * in RestoreBackupRequest.
 */
class BackupPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage backups');
    }

    public function view(User $user, ?Backup $backup = null): bool
    {
        return $user->can('manage backups');
    }

    public function create(User $user): bool
    {
        return $user->can('manage backups');
    }

    public function download(User $user, ?Backup $backup = null): bool
    {
        return $user->can('manage backups') && ($backup === null || $backup->isCompleted());
    }

    public function delete(User $user, ?Backup $backup = null): bool
    {
        return $user->can('manage backups');
    }

    public function restore(User $user, ?Backup $backup = null): bool
    {
        return $user->can('manage backups') && ($backup === null || $backup->isCompleted());
    }
}
