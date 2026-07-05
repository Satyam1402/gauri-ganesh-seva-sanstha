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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_category_id')->nullable()
                ->constrained('activity_categories')->nullOnDelete();
            $table->string('title', 200);
            $table->string('slug', 220)->unique();
            $table->string('short_description', 300)->nullable();
            $table->longText('full_description')->nullable();
            $table->date('activity_date');
            $table->string('location', 150)->nullable();
            $table->string('organizer', 150)->nullable();
            $table->string('status', 20)->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'activity_date'], 'idx_activities_status_date');
            $table->index(['activity_category_id', 'status'], 'idx_activities_category_status');
            $table->index(['is_featured', 'status'], 'idx_activities_featured_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
