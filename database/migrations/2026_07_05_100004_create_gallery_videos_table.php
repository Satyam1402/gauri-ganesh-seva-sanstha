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
        Schema::create('gallery_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gallery_album_id')
                ->constrained('gallery_albums')->cascadeOnDelete();
            $table->string('title', 200);
            $table->string('provider', 20);
            $table->string('video_url', 500)->nullable();

            // Extracted YouTube/Vimeo id so embed URLs never re-parse the
            // original link at render time.
            $table->string('video_id', 100)->nullable();

            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('order_column')->default(0);
            $table->timestamps();

            $table->index(['gallery_album_id', 'is_active', 'order_column'], 'idx_gallery_videos_album_active_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gallery_videos');
    }
};
