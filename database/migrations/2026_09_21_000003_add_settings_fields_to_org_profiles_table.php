<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The Site Settings "Organization", "Contact" and "Social Media" tabs
     * edit the existing org_profiles singleton rather than duplicating it
     * in the settings table; these are the few fields it was missing.
     */
    public function up(): void
    {
        Schema::table('org_profiles', function (Blueprint $table) {
            $table->string('country', 100)->nullable()->after('pin_code');
            $table->string('ngo_registration_no', 100)->nullable()->after('section_12a_no');
            $table->string('about_short', 500)->nullable()->after('established_year');
            $table->string('telegram_url', 250)->nullable()->after('linkedin_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('org_profiles', function (Blueprint $table) {
            $table->dropColumn(['country', 'ngo_registration_no', 'about_short', 'telegram_url']);
        });
    }
};
