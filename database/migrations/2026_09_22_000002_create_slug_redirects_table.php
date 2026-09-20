<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Records every slug change on a public content model so the old URL
     * keeps working (301) instead of breaking inbound links and indexed
     * pages. See App\Traits\HasSlug and App\Services\SlugRedirectService.
     */
    public function up(): void
    {
        Schema::create('slug_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('model_type', 150);
            $table->string('old_slug', 220);
            $table->string('new_slug', 220);
            $table->timestamps();

            $table->unique(['model_type', 'old_slug'], 'uq_slug_redirects_type_old');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slug_redirects');
    }
};
