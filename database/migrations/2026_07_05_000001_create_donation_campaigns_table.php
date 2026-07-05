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
        Schema::create('donation_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->string('slug', 220)->unique();
            $table->string('short_description', 300)->nullable();
            $table->longText('full_description')->nullable();
            $table->decimal('goal_amount', 12, 2)->nullable();
            $table->decimal('raised_amount', 12, 2)->default(0);
            $table->char('currency', 3)->default('INR');
            $table->string('status', 20)->default('draft');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('order_column')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'order_column'], 'idx_campaigns_status_order');
            $table->index(['is_featured', 'status'], 'idx_campaigns_featured_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donation_campaigns');
    }
};
