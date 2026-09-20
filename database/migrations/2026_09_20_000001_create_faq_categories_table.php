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
        Schema::create('faq_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->text('description')->nullable();
            // Active categories are shown publicly; inactive ones are
            // "archived" — kept for their FAQs but hidden from the site.
            $table->boolean('is_active')->default(true);
            $table->smallInteger('order_column')->unsigned()->default(0);
            $table->timestamps();

            $table->index(['is_active', 'order_column'], 'idx_faq_categories_active_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faq_categories');
    }
};
