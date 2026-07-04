<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PagesSeeder extends Seeder
{
    /**
     * Seed the static Page anchors used for SEO metadata attachment.
     */
    public function run(): void
    {
        $home = Page::updateOrCreate(['slug' => 'home'], ['title' => 'Homepage']);

        if ($home->seo === null) {
            $home->seo()->create([
                'meta_title' => 'Gauri Ganesh Seva Sanstha — NGO for Food, Education & Health',
                'meta_description' => 'Gauri Ganesh Seva Sanstha provides food, education, medical care, and hope to families in need. Donate, volunteer, or learn more about our work.',
                'meta_keywords' => 'NGO, seva, donation, charity, Pune, food distribution, education support, medical camp',
                'canonical_url' => url('/'),
                'og_title' => 'Together, We Restore Dignity.',
                'og_description' => 'Gauri Ganesh Seva Sanstha provides food, education, medical care, and hope to families in need.',
                'twitter_card' => 'summary_large_image',
                'schema_type' => 'NGO',
            ]);
        }

        $about = Page::updateOrCreate(['slug' => 'about'], ['title' => 'About Us']);

        if ($about->seo === null) {
            $about->seo()->create([
                'meta_title' => 'About Us — Gauri Ganesh Seva Sanstha',
                'meta_description' => 'Learn about the mission, story, and people behind Gauri Ganesh Seva Sanstha — a registered trust serving families through food, education, and medical care.',
                'meta_keywords' => 'about us, NGO, trust, seva, mission, vision, Pune',
                'canonical_url' => url('/about'),
                'og_title' => 'About Gauri Ganesh Seva Sanstha',
                'og_description' => 'Learn about our mission, our story, and the people behind our work.',
                'twitter_card' => 'summary_large_image',
                'schema_type' => 'AboutPage',
            ]);
        }
    }
}
