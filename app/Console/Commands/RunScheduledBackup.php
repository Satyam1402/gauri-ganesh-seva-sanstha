<?php

namespace App\Console\Commands;

use App\Enums\BackupTrigger;
use App\Enums\BackupType;
use App\Services\BackupService;
use Illuminate\Console\Command;

/**
 * Entry point for scheduled (and ad-hoc CLI) backups. Runs inline by
 * default — the scheduler is already a background process and a server
 * without a queue worker must still get its nightly backup. Pass --queue
 * to hand the run to the queue instead.
 */
class RunScheduledBackup extends Command
{
    protected $signature = 'backups:run
                            {type=database : database, files or full}
                            {--queue : Queue the run instead of executing it now}
                            {--manual : Record the run as manual rather than scheduled}';

    protected $description = 'Create a backup (recorded in the admin Backups panel) — used by the scheduler.';

    public function handle(BackupService $backups): int
    {
        $type = BackupType::tryFrom((string) $this->argument('type'));

        if ($type === null) {
            $this->error('Type must be one of: '.implode(', ', array_keys(BackupType::options())));

            return self::INVALID;
        }

        $trigger = $this->option('manual') ? BackupTrigger::Manual : BackupTrigger::Scheduled;

        if ($this->option('queue')) {
            $backup = $backups->queue($type, $trigger);
            $this->info("Backup #{$backup->id} ({$type->label()}) queued.");

            return self::SUCCESS;
        }

        $backup = $backups->runNow($type, $trigger);

        if ($backup->isCompleted()) {
            $this->info("Backup #{$backup->id} completed: {$backup->path} ({$backup->sizeForHumans()}).");

            return self::SUCCESS;
        }

        $this->error("Backup #{$backup->id} failed: {$backup->failure_reason}");

        return self::FAILURE;
    }
}
