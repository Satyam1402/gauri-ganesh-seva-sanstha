<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Extends the existing polymorphic seo_meta table (one row per
     * Page/Activity/BlogPost/...) rather than adding a second SEO table.
     */
    public function up(): void
    {
        Schema::table('seo_meta', function (Blueprint $table) {
            // "index, follow" etc. Null = inherit the global default.
            $table->string('robots', 30)->nullable()->after('canonical_url');
            $table->string('twitter_title', 70)->nullable()->after('twitter_card');
            $table->string('twitter_description', 200)->nullable()->after('twitter_title');
            $table->foreignId('twitter_image_media_id')->nullable()->after('twitter_description')
                ->constrained('media')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seo_meta', function (Blueprint $table) {
            $table->dropConstrainedForeignId('twitter_image_media_id');
            $table->dropColumn(['robots', 'twitter_title', 'twitter_description']);
        });
    }
};
