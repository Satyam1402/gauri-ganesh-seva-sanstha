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
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_type_id')->nullable()
                ->constrained('partner_types')->nullOnDelete();
            $table->string('name', 150);
            $table->string('slug', 170)->unique();
            $table->string('short_description', 300)->nullable();
            $table->longText('full_description')->nullable();
            $table->string('website_url', 255)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->date('started_on')->nullable();
            $table->date('ended_on')->nullable();
            // Alt text for the logo — logos are images of text, so the
            // accessible name must be editable rather than derived.
            $table->string('logo_alt', 150)->nullable();
            $table->string('status', 20)->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->text('admin_notes')->nullable();
            $table->foreignId('created_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_featured', 'display_order'], 'idx_partners_status_featured_order');
            $table->index(['partner_type_id', 'status', 'display_order'], 'idx_partners_type_status_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
