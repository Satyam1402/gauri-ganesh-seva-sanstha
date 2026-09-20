<?php

namespace Database\Seeders;

use App\Models\PartnerType;
use Illuminate\Database\Seeder;

class PartnerTypesSeeder extends Seeder
{
    /**
     * Default partnership types. Slugs are stable because page sections
     * pull specific types by slug (e.g. "sponsor" on the donate page).
     */
    public function run(): void
    {
        $types = [
            ['name' => 'Partner Organization', 'slug' => 'partner-organization', 'description' => 'Organisations we run programmes with.'],
            ['name' => 'Corporate Partner', 'slug' => 'corporate-partner', 'description' => 'Companies supporting us through CSR and employee volunteering.'],
            ['name' => 'NGO Partner', 'slug' => 'ngo-partner', 'description' => 'Fellow non-profits we collaborate with on the ground.'],
            ['name' => 'Community Partner', 'slug' => 'community-partner', 'description' => 'Local groups, societies and associations.'],
            ['name' => 'Institutional Partner', 'slug' => 'institutional-partner', 'description' => 'Schools, colleges, hospitals and government bodies.'],
            ['name' => 'Sponsor', 'slug' => 'sponsor', 'description' => 'Organisations funding specific drives, camps and campaigns.'],
            ['name' => 'Media Partner', 'slug' => 'media-partner', 'description' => 'Outlets that help us reach more people.'],
            ['name' => 'Supporting Organization', 'slug' => 'supporting-organization', 'description' => 'Organisations providing in-kind support and services.'],
            ['name' => 'Other', 'slug' => 'other', 'description' => null],
        ];

        foreach ($types as $order => $type) {
            PartnerType::updateOrCreate(
                ['slug' => $type['slug']],
                [...$type, 'is_active' => true, 'order_column' => $order],
            );
        }
    }
}
