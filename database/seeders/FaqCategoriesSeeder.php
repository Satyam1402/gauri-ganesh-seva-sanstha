<?php

namespace Database\Seeders;

use App\Models\FaqCategory;
use Illuminate\Database\Seeder;

class FaqCategoriesSeeder extends Seeder
{
    /**
     * Default FAQ categories. Slugs are stable because page sections pull
     * specific categories by slug (donations, volunteers, activities,
     * organization) — see the <x-faq-section> usages.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'General', 'slug' => 'general', 'description' => 'Common questions about who we are and what we do.'],
            ['name' => 'Donations', 'slug' => 'donations', 'description' => 'Giving, tax receipts, and how your money is used.'],
            ['name' => 'Volunteers', 'slug' => 'volunteers', 'description' => 'Joining us, time commitments, and what to expect.'],
            ['name' => 'Activities', 'slug' => 'activities', 'description' => 'Our drives, camps, and community programmes.'],
            ['name' => 'Events', 'slug' => 'events', 'description' => 'Registering for and attending our events.'],
            ['name' => 'Food Distribution', 'slug' => 'food-distribution', 'description' => 'Meal drives and food support.'],
            ['name' => 'Education', 'slug' => 'education', 'description' => 'Study kits, tuition, and school support.'],
            ['name' => 'Medical Assistance', 'slug' => 'medical-assistance', 'description' => 'Health camps and medical help.'],
            ['name' => 'Payment', 'slug' => 'payment', 'description' => 'Payment methods, security, and receipts.'],
            ['name' => 'Organization', 'slug' => 'organization', 'description' => 'Registration, governance, and transparency.'],
        ];

        foreach ($categories as $order => $category) {
            FaqCategory::updateOrCreate(
                ['slug' => $category['slug']],
                [...$category, 'is_active' => true, 'order_column' => $order],
            );
        }
    }
}
