<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Public contact details for the organization. They live on the existing
     * org_profiles singleton (rather than a new settings table) because they
     * are organizational identity data — the Contact page, and later the
     * footer/About page, all read from one cached record.
     */
    public function up(): void
    {
        Schema::table('org_profiles', function (Blueprint $table) {
            $table->string('address_line', 500)->nullable()->after('established_year');
            $table->string('city', 100)->nullable()->after('address_line');
            $table->string('state', 100)->nullable()->after('city');
            $table->string('pin_code', 12)->nullable()->after('state');

            $table->string('phone_primary', 20)->nullable()->after('pin_code');
            $table->string('phone_secondary', 20)->nullable()->after('phone_primary');
            $table->string('email_primary', 150)->nullable()->after('phone_secondary');
            $table->string('email_secondary', 150)->nullable()->after('email_primary');

            $table->string('office_hours', 200)->nullable()->after('email_secondary');
            $table->string('whatsapp_number', 20)->nullable()->after('office_hours');
            $table->string('emergency_phone', 20)->nullable()->after('whatsapp_number');
            $table->string('map_embed_url', 500)->nullable()->after('emergency_phone');

            $table->string('facebook_url', 250)->nullable()->after('map_embed_url');
            $table->string('instagram_url', 250)->nullable()->after('facebook_url');
            $table->string('twitter_url', 250)->nullable()->after('instagram_url');
            $table->string('youtube_url', 250)->nullable()->after('twitter_url');
            $table->string('linkedin_url', 250)->nullable()->after('youtube_url');
        });
    }

    public function down(): void
    {
        Schema::table('org_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'address_line', 'city', 'state', 'pin_code',
                'phone_primary', 'phone_secondary', 'email_primary', 'email_secondary',
                'office_hours', 'whatsapp_number', 'emergency_phone', 'map_embed_url',
                'facebook_url', 'instagram_url', 'twitter_url', 'youtube_url', 'linkedin_url',
            ]);
        });
    }
};
