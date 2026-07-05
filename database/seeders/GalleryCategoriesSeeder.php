<?php

namespace Database\Seeders;

use App\Models\GalleryCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GalleryCategoriesSeeder extends Seeder
{
    /**
     * Demo gallery categories — editable and extendable from the admin CMS.
     */
    public function run(): void
    {
        $categories = [
            'Events',
            'Food Distribution',
            'Medical Camps',
            'Education',
            'Festivals',
            'Volunteers',
        ];

        foreach ($categories as $order => $name) {
            GalleryCategory::updateOrCreate(
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
