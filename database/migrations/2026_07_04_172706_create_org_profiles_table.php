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
        Schema::create('org_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('legal_name', 200)->nullable();
            $table->string('short_name', 100)->nullable();
            $table->string('registration_no', 100)->nullable();
            $table->date('registration_date')->nullable();
            $table->string('pan_no', 20)->nullable();
            $table->string('trust_deed_no', 100)->nullable();
            $table->string('section_80g_no', 100)->nullable();
            $table->string('section_12a_no', 100)->nullable();
            $table->year('established_year')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('org_profiles');
    }
};
