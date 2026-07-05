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
        Schema::create('gallery_albums', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gallery_category_id')->nullable()
                ->constrained('gallery_categories')->nullOnDelete();
            $table->string('title', 200);
            $table->string('slug', 220)->unique();
            $table->text('description')->nullable();
            $table->date('event_date')->nullable();
            $table->string('location', 150)->nullable();
            $table->string('status', 20)->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('order_column')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'event_date'], 'idx_gallery_albums_status_date');
            $table->index(['gallery_category_id', 'status'], 'idx_gallery_albums_category_status');
            $table->index(['is_featured', 'status'], 'idx_gallery_albums_featured_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gallery_albums');
    }
};
