<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit trail for backup & restore operations (created / completed /
 * failed / downloaded / deleted / restore initiated / completed / failed /
 * cleanup). The project has no general activity log yet (that module is a
 * later phase); this table is deliberately narrow so it can be folded into
 * it when it arrives.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('backup_id')->nullable()->constrained('backups')->nullOnDelete();
            $table->string('event', 40);           // App\Enums\BackupLogEvent
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('message', 500);
            $table->json('context')->nullable();   // non-sensitive details (type, disk, size, scope)
            $table->timestamp('created_at')->useCurrent();

            $table->index(['event', 'created_at'], 'idx_backup_logs_event_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_logs');
    }
};
