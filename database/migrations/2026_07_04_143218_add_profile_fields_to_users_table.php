<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
            $table->foreignId('avatar_media_id')->nullable()->after('phone')
                ->constrained('media')->nullOnDelete();
            $table->enum('status', ['active', 'suspended'])->default('active')->after('avatar_media_id');
            $table->timestamp('last_login_at')->nullable()->after('status');
            $table->softDeletes();

            $table->index('status', 'idx_users_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_status');
            $table->dropConstrainedForeignId('avatar_media_id');
            $table->dropColumn(['phone', 'status', 'last_login_at', 'deleted_at']);
        });
    }
};
