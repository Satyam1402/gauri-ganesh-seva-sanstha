<?php

use Spatie\Backup\Notifications\Notifiable;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification;
use Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification;
use Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes;

/*
|--------------------------------------------------------------------------
| Backup & Restore (spatie/laravel-backup v9 + App\Services\BackupService)
|--------------------------------------------------------------------------
|
| Decisions (see docs/BACKUPS.md):
|  - Backed up: the database and storage/app (public media, private
|    uploads such as identity documents/resumes/attachments) plus the
|    non-secret configuration documentation (.env.example, docs/,
|    composer/package manifests). Application code lives in git.
|  - NOT backed up: .env and any other secret (APP_KEY, gateway keys, SMTP)
|    — keep those in your deployment secret store; caches, logs, sessions,
|    node_modules, vendor, and the backup archives themselves.
|  - Stored on the private "backups" disk (storage/app/backups — outside
|    public/) and optionally mirrored to an S3-compatible bucket.
|  - Encrypted as AES-256 zips whenever BACKUP_ARCHIVE_PASSWORD is set.
|  - Retained by the configurable policy below; cleanup never removes the
|    newest backup and the app refuses to delete the last valid one.
|
*/

return [

    'backup' => [

        /*
         * The name of this application. You can use this name to monitor
         * the backups. Also used as the directory name on every disk.
         */
        'name' => env('BACKUP_NAME', 'ggss'),

        'source' => [

            'files' => [

                /*
                 * The list of directories and files that will be included in the backup.
                 */
                'include' => [
                    storage_path('app/public'),
                    storage_path('app/private'),
                    base_path('.env.example'),
                    base_path('composer.json'),
                    base_path('composer.lock'),
                    base_path('package.json'),
                    base_path('package-lock.json'),
                    base_path('docs'),
                ],

                /*
                 * These directories and files will be excluded from the backup.
                 * Directories used by the backup process will automatically be excluded.
                 */
                'exclude' => [
                    storage_path('app/backups'),
                    storage_path('app/backup-temp'),
                    storage_path('app/private/backups'),
                    storage_path('app/private/backup-temp'),
                ],

                /*
                 * Determines if symlinks should be followed.
                 */
                'follow_links' => false,

                /*
                 * Determines if it should avoid unreadable folders.
                 */
                'ignore_unreadable_directories' => true,

                /*
                 * This path is used to make directories in resulting zip-file relative
                 * Set to `null` to include complete absolute path
                 * Example: base_path()
                 */
                'relative_path' => base_path(),
            ],

            /*
             * The names of the connections to the databases that should be backed up
             * MySQL, PostgreSQL, SQLite and Mongo databases are supported.
             *
             * The content of the database dump may be customized for each connection
             * by adding a 'dump' key to the connection settings in config/database.php.
             */
            'databases' => [
                env('DB_CONNECTION', 'mysql'),
            ],
        ],

        /*
         * The database dump can be compressed to decrease disk space usage.
         */
        'database_dump_compressor' => null,

        /*
         * If specified, the database dumped file name will contain a timestamp (e.g.: 'Y-m-d-H-i-s').
         */
        'database_dump_file_timestamp_format' => null,

        /*
         * The base of the dump filename, either 'database' or 'connection'
         */
        'database_dump_filename_base' => 'database',

        /*
         * The file extension used for the database dump files.
         */
        'database_dump_file_extension' => '',

        'destination' => [

            /*
             * The compression algorithm to be used for creating the zip archive.
             */
            'compression_method' => ZipArchive::CM_DEFAULT,

            /*
             * The compression level corresponding to the used algorithm; an integer between 0 and 9.
             */
            'compression_level' => 6,

            /*
             * The filename prefix used for the backup zip file. The app sets the
             * full filename per run (see BackupService::filenameFor()).
             */
            'filename_prefix' => '',

            /*
             * The disk names on which the backups will be stored. "backups" is a
             * private local disk (storage/app/backups); "backups_s3" is added
             * automatically when BACKUP_S3_ENABLED=true.
             */
            'disks' => array_values(array_filter([
                'backups',
                env('BACKUP_S3_ENABLED', false) ? 'backups_s3' : null,
            ])),
        ],

        /*
         * The directory where the temporary files will be stored.
         */
        'temporary_directory' => storage_path('app/backup-temp'),

        /*
         * The password to be used for archive encryption. Set to `null` to disable
         * encryption (not recommended: archives contain donor, volunteer and
         * identity-document data).
         */
        'password' => env('BACKUP_ARCHIVE_PASSWORD') ?: null,

        /*
         * The encryption algorithm to be used for archive encryption.
         * You can set it to `null` or `false` to disable encryption.
         */
        'encryption' => 'default',

        /**
         * The number of attempts, in case the backup command encounters an exception
         */
        'tries' => 1,

        /**
         * The number of seconds to wait before attempting a new backup if the previous try failed
         */
        'retry_delay' => 0,
    ],

    /*
     * Spatie's own mail/Slack notifications are switched off: outcomes are
     * routed through the project's notification system instead
     * (App\Notifications\Backups\* via App\Listeners\Backups\*).
     */
    'notifications' => [

        'notifications' => [
            BackupHasFailedNotification::class => [],
            UnhealthyBackupWasFoundNotification::class => [],
            CleanupHasFailedNotification::class => [],
            BackupWasSuccessfulNotification::class => [],
            HealthyBackupWasFoundNotification::class => [],
            CleanupWasSuccessfulNotification::class => [],
        ],

        'notifiable' => Notifiable::class,

        'mail' => [
            'to' => env('MAIL_FROM_ADDRESS', 'no-reply@example.com'),
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'no-reply@example.com'),
                'name' => env('MAIL_FROM_NAME', 'Backups'),
            ],
        ],

        'slack' => [
            'webhook_url' => '',
            'channel' => null,
            'username' => null,
            'icon' => null,
        ],

        'discord' => [
            'webhook_url' => '',
            'username' => '',
            'avatar_url' => '',
        ],
    ],

    /*
     * Health checks used by `backup:monitor` and the admin Backups page.
     */
    'monitor_backups' => [
        [
            'name' => env('BACKUP_NAME', 'ggss'),
            'disks' => array_values(array_filter([
                'backups',
                env('BACKUP_S3_ENABLED', false) ? 'backups_s3' : null,
            ])),
            'health_checks' => [
                MaximumAgeInDays::class => (int) env('BACKUP_MAX_AGE_DAYS', 1),
                MaximumStorageInMegabytes::class => (int) env('BACKUP_MAX_STORAGE_MB', 5000),
            ],
        ],
    ],

    /*
     * Retention. All values are environment-configurable so production can
     * keep more (or less) without a code change. The default strategy keeps
     * the newest backup no matter what.
     */
    'cleanup' => [
        'strategy' => DefaultStrategy::class,

        'default_strategy' => [
            // Keep every backup made in the last N days.
            'keep_all_backups_for_days' => (int) env('BACKUP_KEEP_ALL_DAYS', 7),
            // Then one per day for N days...
            'keep_daily_backups_for_days' => (int) env('BACKUP_KEEP_DAILY_DAYS', 16),
            // ...one per week for N weeks...
            'keep_weekly_backups_for_weeks' => (int) env('BACKUP_KEEP_WEEKLY_WEEKS', 8),
            // ...one per month for N months...
            'keep_monthly_backups_for_months' => (int) env('BACKUP_KEEP_MONTHLY_MONTHS', 6),
            // ...and one per year for N years.
            'keep_yearly_backups_for_years' => (int) env('BACKUP_KEEP_YEARLY_YEARS', 2),
            // After applying the rules above, delete the oldest backups until this cap is met.
            'delete_oldest_backups_when_using_more_megabytes_than' => (int) env('BACKUP_MAX_STORAGE_MB', 5000),
        ],

        'tries' => 1,
        'retry_delay' => 0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Application-level settings (not part of spatie/laravel-backup)
    |--------------------------------------------------------------------------
    */

    'app' => [

        // Queue for backup/restore jobs. Runs inline when QUEUE_CONNECTION=sync.
        'queue' => env('BACKUP_QUEUE', 'default'),

        // Seconds a single backup or restore job may run before the worker kills it.
        'job_timeout' => (int) env('BACKUP_JOB_TIMEOUT', 3600),

        // Directory (not the executable) holding the `mysql` client used for
        // database restores, e.g. C:\xampp8.2\mysql\bin or /usr/bin. Empty = PATH.
        'mysql_binary_path' => env('DB_DUMP_BINARY_PATH', ''),

        // Admin-panel downloads and manual backup runs are rate limited.
        'download_rate_limit' => env('BACKUP_DOWNLOAD_RATE_LIMIT', '10,1'),
        'run_rate_limit' => env('BACKUP_RUN_RATE_LIMIT', '5,10'),
    ],
];
