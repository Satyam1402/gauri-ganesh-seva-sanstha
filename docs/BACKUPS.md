# Backup & Restore

Built on **spatie/laravel-backup v9** (v10 needs PHP 8.3; this platform runs 8.2) with an application layer for history, status, audit logging, notifications and a controlled restore workflow.

## What is (and is not) backed up

| Included | Why |
|---|---|
| Database (`mysqldump`, single transaction) | All donations, applications, enquiries, users, settings, content. |
| `storage/app/public` | Public media (images, gallery, logos). |
| `storage/app/private` | Private uploads: identity documents, resumes, enquiry attachments. |
| `.env.example`, `composer.*`, `package*.json`, `docs/` | Configuration *documentation* — enough to rebuild an environment without leaking secrets. |

| Excluded | Why |
|---|---|
| `.env` | Contains `APP_KEY`, DB/SMTP/gateway secrets. Keep it in your deployment secret store; a restore onto a fresh server needs the **same `APP_KEY`** (encrypted data, signed URLs) and the same `BACKUP_ARCHIVE_PASSWORD`. |
| Application code, `vendor/`, `node_modules/`, `public/build` | Lives in git / is rebuilt by `composer install` + `npm run build`. |
| `storage/framework`, logs, sessions, caches | Transient. |
| `storage/app/backups`, `storage/app/backup-temp` | The archives themselves. |

## Where backups live

* **Primary:** the private `backups` disk → `storage/app/backups/ggss/` (never under `public/`, no `url`, `serve => false`). Downloads are streamed only through `admin.backups.download` (permission `manage backups`, rate limited, audit logged).
* **Off-site (optional):** set `BACKUP_S3_ENABLED=true` plus the `BACKUP_S3_*` variables and run `composer require league/flysystem-aws-s3-v3 "^3.0"`. Works with AWS S3 or any S3-compatible store (MinIO, Backblaze B2, DigitalOcean Spaces via `BACKUP_S3_ENDPOINT`). Archives are written to both disks on every run.

## Encryption

Archives are AES-256 zips whenever `BACKUP_ARCHIVE_PASSWORD` is set (the panel shows a red "Off" card when it is not). Store that password *outside* the backups. The password is required to inspect, download-and-open, or restore an archive; a wrong password is detected during validation before any restore starts.

## Types, schedule and cron

| Type | Command | Schedule (app timezone) |
|---|---|---|
| Database | `php artisan backups:run database` | daily 01:30 |
| Files | `php artisan backups:run files` | daily 02:00 |
| Full | `php artisan backups:run full` | weekly, Sunday 03:00 |
| Retention | `php artisan backups:clean` | daily 04:00 |

The schedule is declared in `routes/console.php`. **It only runs if the server has one cron entry:**

```
* * * * * cd /var/www/gauri-ganesh-seva-sanstha && php artisan schedule:run >> /dev/null 2>&1
```

(Windows/XAMPP: a Task Scheduler task running `php artisan schedule:run` every minute, or `php artisan schedule:work` in a console for development.)

Scheduled runs execute **inline** in the scheduler process, so nightly backups work even without a queue worker. Manual runs from the admin panel are queued (`BACKUP_QUEUE`) — run `php artisan queue:work` in production; with `QUEUE_CONNECTION=sync` they execute during the request. The panel's "Scheduler" card turns amber when no scheduled backup has run for 2 days.

Also set `DB_DUMP_BINARY_PATH` to the folder holding `mysqldump`/`mysql` when they are not on the PATH (XAMPP: `C:/xampp8.2/mysql/bin`).

## Retention

Configured in `.env` (`BACKUP_KEEP_*`, `BACKUP_MAX_STORAGE_MB`) and applied by `backups:clean`:
keep **all** for 7 days → one per **day** for 16 days → one per **week** for 8 weeks → one per **month** for 6 months → one per **year** for 2 years → then trim oldest to the MB cap. Spatie never deletes the newest archive, and the application refuses to delete the last remaining valid backup (manual delete included). Rows whose archive was removed are marked **Expired**; archives found on disk without a row (e.g. from a direct `backup:run`) are registered by the rescan.

## Statuses

`Pending` (queued) → `Running` → `Completed` | `Failed` (reason recorded, secrets scrubbed, retry offered). `Deleted` = removed by an admin; `Expired` = removed by retention. A worker-killed job is marked Failed by `RunBackupJob::failed()`, never left Running.

## Failure handling

Failures are logged (`storage/logs`), written to the backup audit log with a **scrubbed** reason (DB password, archive password, S3 keys, `APP_KEY` and `--password=` fragments replaced by `***`), and sent to every active user holding `manage backups` through the existing notification system (`BackupFailedAlert`: in-app + email). The admin panel shows the reason and a **Retry** action that starts a new run (the failed one stays on record).

## Restore (controlled workflow)

1. **Confirm page** (`/admin/backups/{id}/restore`): the archive is opened and validated (zip readable, password correct, contents listed) before the form is shown. Scopes offered depend on what the archive holds and on the environment (database restores need MySQL/MariaDB + the `mysql` client).
2. **Confirmation:** the admin must select a scope, type `RESTORE`, re-enter their current password and tick the acknowledgement. Requires `manage backups`; rate limited to 3 attempts per 10 minutes.
3. **Job** (`RestoreBackupJob`, exclusive lock with backup runs): takes a **safety backup** of the same scope first — if it fails the restore is aborted — then:
   * *Database:* the dump is streamed into `mysql` (password via `MYSQL_PWD`, never on the command line).
   * *Files:* entries under `storage/app/public|private` are written back in place. Existing files are overwritten; files not in the archive are **not deleted**. Code, docs and manifests are never restored.
   * Caches and the permission cache are flushed; the backup history is rescanned (the history table is part of what was restored).
4. **Audit + notification:** `restore_initiated`, `restore_completed` / `restore_failed` with user, IP and scope; `BackupRestoredAlert` / `RestoreFailedAlert` to all backup managers.

**Limitations (by design):** no cross-server "bare metal" restore from the panel — a new server needs the code (git), `.env` (same `APP_KEY` and `BACKUP_ARCHIVE_PASSWORD`), `php artisan migrate` is *not* needed after a DB restore (the dump includes the `migrations` table). Enable maintenance mode before restoring so visitors are not mid-transaction. Restoring a database taken before the current backup rows will also roll back the backup history; the rescan re-registers archives that still exist on disk.

## Audit log

`backup_logs` (append-only) records: queued/started, completed, failed, downloaded, deleted, expired, cleanup run, restore initiated/completed/failed — with user, IP and non-sensitive context. Viewable at **Admin → Backups → Audit Log**. When the general Audit Log module is built, this table is the natural feed for it.

## Permissions

`manage backups` — granted to Super Admin and Admin by `RolesAndPermissionsSeeder`. It also gates the `backup` notification category.
