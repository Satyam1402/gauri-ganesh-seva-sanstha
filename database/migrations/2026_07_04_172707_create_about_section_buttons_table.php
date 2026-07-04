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
        Schema::create('about_section_buttons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('about_section_id')->constrained()->cascadeOnDelete();
            $table->string('label', 60);
            $table->string('url', 255);
            $table->string('variant', 20)->default('primary');
            $table->smallInteger('order_column')->unsigned()->default(0);
            $table->timestamps();

            $table->index(['about_section_id', 'order_column'], 'idx_about_section_buttons_section_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('about_section_buttons');
    }
};
