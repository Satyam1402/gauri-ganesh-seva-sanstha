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
        Schema::create('home_section_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('home_section_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            $table->string('subtitle', 200)->nullable();
            $table->text('description')->nullable();
            $table->string('icon', 50)->nullable();
            $table->string('link_url', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->smallInteger('order_column')->unsigned()->default(0);
            $table->timestamps();

            $table->index(['home_section_id', 'is_active', 'order_column'], 'idx_home_section_items_section_active_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('home_section_items');
    }
};
