<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BlogCategoriesSeeder extends Seeder
{
    /**
     * Demo blog categories — editable and extendable from the admin CMS.
     */
    public function run(): void
    {
        $categories = [
            'News' => 'Latest updates and announcements from the organisation.',
            'Events' => 'Reports and recaps from our camps, drives, and community events.',
            'Success Stories' => 'Real stories of lives changed through your support.',
            'Health' => 'Health camps, checkups, and medical outreach coverage.',
            'Education' => 'Education drives, scholarships, and learning programs.',
            'Food Distribution' => 'Meal drives and food relief efforts on the ground.',
            'Medical Camps' => 'Free health checkup and treatment camp reports.',
            'Awareness' => 'Articles that spread awareness on social causes.',
            'Announcements' => 'Official notices and organisational announcements.',
            'Press Releases' => 'Media statements and press coverage.',
            'General' => 'Everything else from the field and behind the scenes.',
        ];

        $order = 0;

        foreach ($categories as $name => $description) {
            BlogCategory::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'description' => $description,
                    'is_active' => true,
                    'order_column' => $order++,
                ]
            );
        }
    }
}
