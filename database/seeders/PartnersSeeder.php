<?php

namespace Database\Seeders;

use App\Enums\PartnerStatus;
use App\Models\Partner;
use App\Models\PartnerType;
use Illuminate\Database\Seeder;

class PartnersSeeder extends Seeder
{
    /**
     * Demo partners so every public view has content to render while
     * developing. Partner names and logos are real-world claims, so this
     * seeder refuses to run in production — real partners must be added
     * through the admin panel there. No logos are seeded; cards fall back
     * to the organisation name.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('PartnersSeeder skipped: demo partners are never seeded in production.');

            return;
        }

        if (Partner::query()->exists()) {
            return;
        }

        $types = PartnerType::query()->pluck('id', 'slug');

        $partners = [
            ['sponsor', 'Sunrise CSR Trust', 'Funds our annual school-kit drive and two medical camps a year.', 'https://example.org/sunrise', 'Pune', 'Maharashtra', '2022-04-01', true],
            ['corporate-partner', 'ABC Foundation', 'Employee volunteering and matched-giving programme partner since 2021.', 'https://example.org/abc-foundation', 'Mumbai', 'Maharashtra', '2021-01-15', true],
            ['institutional-partner', 'Community Health Partners Hospital', 'Provides doctors, nurses and referrals for our free health camps.', 'https://example.org/chp', 'Pune', 'Maharashtra', '2020-08-01', true],
            ['ngo-partner', 'Anna Seva Network', 'Fellow NGO we co-run the Sunday community meal drive with.', null, 'Pune', 'Maharashtra', '2019-06-01', true],
            ['community-partner', 'Hadapsar Residents Welfare Association', 'Hosts our monthly ration distribution and winter clothing drive.', null, 'Pune', 'Maharashtra', '2020-11-01', false],
            ['media-partner', 'Pune Community Radio 90.4', 'Broadcasts our camp announcements and volunteer appeals.', 'https://example.org/pcr', 'Pune', 'Maharashtra', '2023-02-01', false],
            ['supporting-organization', 'Green Print Studio', 'Prints our banners, study kits and awareness material at cost.', 'https://example.org/greenprint', 'Pune', 'Maharashtra', '2022-09-01', false],
            ['partner-organization', 'Vidya Setu Learning Centre', 'Runs the evening tuition classes for our education programme.', null, 'Pune', 'Maharashtra', '2021-07-01', false],
        ];

        foreach ($partners as $order => [$slug, $name, $description, $website, $city, $state, $since, $featured]) {
            Partner::create([
                'partner_type_id' => $types[$slug] ?? null,
                'name' => $name,
                'short_description' => $description,
                'website_url' => $website,
                'city' => $city,
                'state' => $state,
                'country' => 'India',
                'started_on' => $since,
                'status' => PartnerStatus::Active->value,
                'is_featured' => $featured,
                'display_order' => $order,
                'admin_notes' => 'Demo seed data — replace with real partners before launch.',
            ]);
        }

        // One inactive and one draft so the admin listing shows the workflow.
        Partner::create([
            'partner_type_id' => $types['sponsor'] ?? null,
            'name' => 'Past Sponsor Ltd',
            'short_description' => 'Sponsored the 2020 winter drive.',
            'city' => 'Pune',
            'country' => 'India',
            'started_on' => '2020-10-01',
            'ended_on' => '2021-03-31',
            'status' => PartnerStatus::Inactive->value,
            'display_order' => 20,
            'admin_notes' => 'Partnership ended; keep for records.',
        ]);

        Partner::create([
            'partner_type_id' => $types['corporate-partner'] ?? null,
            'name' => 'Prospective Corp',
            'short_description' => 'CSR partnership under discussion.',
            'country' => 'India',
            'status' => PartnerStatus::Draft->value,
            'display_order' => 21,
            'admin_notes' => 'Awaiting signed MoU before activating.',
        ]);
    }
}
