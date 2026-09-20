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
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('designation', 150)->nullable();
            $table->string('organization', 150)->nullable();
            $table->string('location', 150)->nullable();
            $table->text('content');
            $table->unsignedTinyInteger('rating')->nullable();
            $table->string('type', 30);
            $table->string('status', 20)->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamp('published_at')->nullable();

            // Optional link to the activity or campaign the testimonial is
            // about, so those pages can show their own voices first.
            $table->nullableMorphs('testimonialable');

            // Consent is stored as an explicit flag plus the date it was
            // given — a testimonial can never be published without it.
            $table->boolean('consent_given')->default(false);
            $table->timestamp('consented_at')->nullable();

            $table->text('admin_notes')->nullable();
            $table->foreignId('created_by')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_featured', 'display_order'], 'idx_testimonials_status_featured_order');
            $table->index(['type', 'status'], 'idx_testimonials_type_status');
            $table->index('published_at', 'idx_testimonials_published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
