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
        OrgProfile::query()->firstOrCreate([], [
            'legal_name' => 'Gauri Ganesh Seva Sanstha',
            'short_name' => 'GGSS',
            'registration_no' => 'MH/1234/PUNE/2015',
            'registration_date' => '2015-06-12',
            'pan_no' => 'AABTG1234C',
            'trust_deed_no' => 'TD/5678/2015',
            'section_80g_no' => '80G/2016/00123',
            'section_12a_no' => '12A/2015/00456',
            'established_year' => 2015,
        ]);
    }
}
