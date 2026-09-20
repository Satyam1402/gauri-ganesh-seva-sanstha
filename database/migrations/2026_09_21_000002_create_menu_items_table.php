<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Self-referencing menu items keyed by location (header, footer
     * columns). A separate `menus` table is unnecessary while locations
     * are a fixed, code-defined set — see App\Enums\MenuLocation.
     */
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('location', 30);
            $table->foreignId('parent_id')->nullable()
                ->constrained('menu_items')->cascadeOnDelete();
            $table->string('label', 100);
            // route  → named internal route (route_name)
            // path   → relative internal path (url, e.g. /campaigns)
            // url    → external http(s) address (url)
            $table->string('link_type', 10)->default('route');
            $table->string('route_name', 100)->nullable();
            $table->string('url', 255)->nullable();
            $table->boolean('open_in_new_tab')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('order_column')->default(0);
            $table->timestamps();

            $table->index(['location', 'is_active', 'order_column'], 'idx_menu_items_location_active_order');
            $table->index(['parent_id', 'order_column'], 'idx_menu_items_parent_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
