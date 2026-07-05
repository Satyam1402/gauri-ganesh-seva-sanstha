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
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_category_id')->nullable()
                ->constrained('blog_categories')->nullOnDelete();
            $table->foreignId('user_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->string('title', 200);
            $table->string('slug', 220)->unique();
            $table->string('excerpt', 500)->nullable();
            $table->longText('content');
            // Future published_at = scheduled publishing: the post goes live
            // automatically once the timestamp passes.
            $table->dateTime('published_at')->nullable();
            $table->smallInteger('reading_minutes')->unsigned()->default(1);
            $table->unsignedBigInteger('views_count')->default(0);
            $table->boolean('allow_comments')->default(true);
            $table->string('status', 20)->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at'], 'idx_blog_posts_status_published');
            $table->index(['blog_category_id', 'status'], 'idx_blog_posts_category_status');
            $table->index(['is_featured', 'status'], 'idx_blog_posts_featured_status');
            $table->index('views_count', 'idx_blog_posts_views');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};
