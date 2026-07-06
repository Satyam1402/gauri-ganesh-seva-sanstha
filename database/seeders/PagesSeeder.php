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

        $volunteer = Page::updateOrCreate(['slug' => 'volunteer'], ['title' => 'Become a Volunteer']);

        if ($volunteer->seo === null) {
            $volunteer->seo()->create([
                'meta_title' => 'Become a Volunteer — Gauri Ganesh Seva Sanstha',
                'meta_description' => 'Join Gauri Ganesh Seva Sanstha as a volunteer. Help with food distribution, education, medical camps, and community outreach. Apply online in minutes.',
                'meta_keywords' => 'volunteer, NGO volunteering, seva, Pune, apply volunteer, community service',
                'canonical_url' => url('/volunteer'),
                'og_title' => 'Become a Volunteer',
                'og_description' => 'Lend your time and skills — help us restore dignity in the communities we serve.',
                'twitter_card' => 'summary_large_image',
                'schema_type' => 'WebPage',
            ]);
        }

        $contact = Page::updateOrCreate(['slug' => 'contact'], ['title' => 'Contact Us']);

        if ($contact->seo === null) {
            $contact->seo()->create([
                'meta_title' => 'Contact Us — Gauri Ganesh Seva Sanstha',
                'meta_description' => 'Reach Gauri Ganesh Seva Sanstha by phone, email, WhatsApp, or our enquiry form — for donations, volunteering, partnerships, media, and general questions.',
                'meta_keywords' => 'contact, NGO, enquiry, Pune, donation help, volunteer contact, partnership',
                'canonical_url' => url('/contact'),
                'og_title' => 'Contact Gauri Ganesh Seva Sanstha',
                'og_description' => 'Questions about donations, volunteering, or partnerships? We usually respond within 2–3 working days.',
                'twitter_card' => 'summary_large_image',
                'schema_type' => 'ContactPage',
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
