<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            AdminUserSeeder::class,
            PagesSeeder::class,
            HomeSectionsSeeder::class,
            OrgProfileSeeder::class,
            AboutSectionsSeeder::class,
            ActivityCategoriesSeeder::class,
            ActivitiesSeeder::class,
            DonationCampaignsSeeder::class,
            GalleryCategoriesSeeder::class,
            GalleryAlbumsSeeder::class,
            EventCategoriesSeeder::class,
            EventsSeeder::class,
            BlogCategoriesSeeder::class,
            BlogPostsSeeder::class,
        ]);
    }
}
