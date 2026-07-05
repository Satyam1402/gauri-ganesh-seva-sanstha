<?php

namespace Database\Seeders;

use App\Models\GalleryAlbum;
use App\Models\GalleryCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GalleryAlbumsSeeder extends Seeder
{
    /**
     * Demo gallery albums — photos are uploaded from the admin, but albums
     * are seeded so the gallery pages have structure to render. YouTube
     * videos are seeded where possible since they need no local files.
     */
    public function run(): void
    {
        $albums = [
            [
                'category' => 'Events',
                'title' => 'Ganesh Chaturthi Celebration 2025',
                'description' => 'Highlights from our annual Ganesh Chaturthi celebrations — aarti, prasad distribution, and cultural programs with the whole community.',
                'event_date' => now()->subDays(30)->toDateString(),
                'location' => 'GGSS Community Center, Pune',
                'status' => 'published',
                'is_featured' => true,
            ],
            [
                'category' => 'Food Distribution',
                'title' => 'Sunday Meal Drive — Yerawada',
                'description' => 'Our volunteers preparing and serving hot meals to families in the Yerawada community.',
                'event_date' => now()->subDays(12)->toDateString(),
                'location' => 'Yerawada, Pune',
                'status' => 'published',
                'is_featured' => true,
            ],
            [
                'category' => 'Medical Camps',
                'title' => 'Free Health Checkup Camp — Hadapsar',
                'description' => 'Doctors and volunteer nurses conducting free health screenings for over 200 residents.',
                'event_date' => now()->subDays(25)->toDateString(),
                'location' => 'Community Hall, Hadapsar',
                'status' => 'published',
                'is_featured' => false,
            ],
            [
                'category' => 'Education',
                'title' => 'School Kit Distribution Day',
                'description' => 'Smiles all around as 120 children received notebooks, bags, and uniforms for the new academic year.',
                'event_date' => now()->subDays(60)->toDateString(),
                'location' => 'Government School, Pune',
                'status' => 'published',
                'is_featured' => true,
            ],
            [
                'category' => 'Volunteers',
                'title' => 'Volunteer Orientation Batch 12',
                'description' => 'Welcoming our newest batch of volunteers with a day of orientation and team building.',
                'event_date' => now()->subDays(8)->toDateString(),
                'location' => 'GGSS Community Center',
                'status' => 'published',
                'is_featured' => false,
            ],
            [
                'category' => 'Festivals',
                'title' => 'Diwali With Our Extended Family',
                'description' => 'Celebrating the festival of lights with sweets, diyas, and new clothes for the children we support.',
                'event_date' => now()->subDays(90)->toDateString(),
                'location' => 'Pune',
                'status' => 'draft',
                'is_featured' => false,
            ],
        ];

        foreach ($albums as $order => $data) {
            $category = GalleryCategory::where('name', $data['category'])->first();

            GalleryAlbum::updateOrCreate(
                ['slug' => Str::slug($data['title'])],
                [
                    'gallery_category_id' => $category?->id,
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'event_date' => $data['event_date'],
                    'location' => $data['location'],
                    'status' => $data['status'],
                    'is_featured' => $data['is_featured'],
                    'order_column' => $order,
                ]
            );
        }
    }
}
