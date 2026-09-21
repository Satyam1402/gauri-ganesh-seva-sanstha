<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per backup run. spatie/laravel-backup only knows about archives
 * that exist on disk; this table adds what the admin panel and the audit
 * trail need — runs that are still queued, runs that failed (and why),
 * who started them, and archives that retention has since removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backups', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 20);            // App\Enums\BackupType
            $table->string('status', 20);          // App\Enums\BackupStatus
            $table->string('trigger', 20);         // App\Enums\BackupTrigger
            $table->string('disk', 50);            // primary destination disk
            $table->string('filename')->nullable();  // e.g. db-2026-09-21-01-30-00.zip
            $table->string('path')->nullable();      // path on the disk: {backup name}/{filename}
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->boolean('encrypted')->default(false);
            $table->json('disks')->nullable();       // every disk the archive was written to
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('failure_reason')->nullable(); // sanitised, no credentials
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at'], 'idx_backups_status_created');
            $table->index('type', 'idx_backups_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};
