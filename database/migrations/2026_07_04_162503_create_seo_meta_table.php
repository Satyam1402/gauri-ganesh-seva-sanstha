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
        Schema::create('seo_meta', function (Blueprint $table) {
            $table->id();
            $table->string('seo_metable_type', 150);
            $table->unsignedBigInteger('seo_metable_id');
            $table->string('meta_title', 70)->nullable();
            $table->string('meta_description', 160)->nullable();
            $table->string('meta_keywords', 255)->nullable();
            $table->string('canonical_url', 255)->nullable();
            $table->string('og_title', 70)->nullable();
            $table->string('og_description', 200)->nullable();
            $table->foreignId('og_image_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('twitter_card', 30)->default('summary_large_image');
            $table->string('schema_type', 50)->nullable();
            $table->json('structured_data')->nullable();
            $table->timestamps();

            $table->unique(['seo_metable_type', 'seo_metable_id'], 'uq_seo_meta_owner');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_meta');
    }
};
