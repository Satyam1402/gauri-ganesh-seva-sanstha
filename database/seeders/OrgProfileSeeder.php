<?php

namespace Database\Seeders;

use App\Models\OrgProfile;
use Illuminate\Database\Seeder;

class OrgProfileSeeder extends Seeder
{
    /**
     * Seed the singleton organization profile with placeholder legal details.
     */
    public function run(): void
    {
        $contactDefaults = [
            'address_line' => '123 Seva Marg, Shivaji Nagar',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'pin_code' => '411005',
            'phone_primary' => '+91 98765 43210',
            'email_primary' => 'contact@ggss.org',
            'office_hours' => 'Mon–Sat, 10:00 AM – 6:00 PM',
            'whatsapp_number' => '+91 98765 43210',
        ];

        $profile = OrgProfile::query()->firstOrCreate([], [
            'legal_name' => 'Gauri Ganesh Seva Sanstha',
            'short_name' => 'GGSS',
            'registration_no' => 'MH/1234/PUNE/2015',
            'registration_date' => '2015-06-12',
            'pan_no' => 'AABTG1234C',
            'trust_deed_no' => 'TD/5678/2015',
            'section_80g_no' => '80G/2016/00123',
            'section_12a_no' => '12A/2015/00456',
            'established_year' => 2015,
            ...$contactDefaults,
        ]);

        // Backfill contact fields (added in Phase 11) on rows that predate them.
        if ($profile->email_primary === null && $profile->phone_primary === null) {
            $profile->update($contactDefaults);
        }
    }
}
