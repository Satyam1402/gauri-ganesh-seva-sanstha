<?php

namespace Database\Seeders;

use App\Models\ActivityCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ActivityCategoriesSeeder extends Seeder
{
    /**
     * Demo activity categories — editable and extendable from the admin CMS.
     */
    public function run(): void
    {
        $categories = [
            'Free Food Distribution',
            'Clothes Distribution',
            'Medical Camps',
            'Blood Donation',
            'Education Support',
            'Women Empowerment',
            'Disaster Relief',
            'Tree Plantation',
            'Animal Welfare',
        ];

        foreach ($categories as $order => $name) {
            ActivityCategory::updateOrCreate(
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
