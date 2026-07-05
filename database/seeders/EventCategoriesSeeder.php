<?php

namespace Database\Seeders;

use App\Models\EventCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EventCategoriesSeeder extends Seeder
{
    /**
     * Demo event categories — editable and extendable from the admin CMS.
     */
    public function run(): void
    {
        $categories = [
            'Food Distribution',
            'Medical Camp',
            'Blood Donation Camp',
            'Tree Plantation',
            'Education Drive',
            'Blanket Distribution',
            'Fundraising Event',
            'Awareness Campaign',
            'Volunteer Meeting',
            'Festival Celebration',
        ];

        foreach ($categories as $order => $name) {
            EventCategory::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'is_active' => true,
                    'order_column' => $order,
                ]
            );
        }
    }
}
