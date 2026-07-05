<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\ActivityCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ActivitiesSeeder extends Seeder
{
    /**
     * Demo activities — editable and extendable from the admin CMS.
     */
    public function run(): void
    {
        $activities = [
            [
                'category' => 'Free Food Distribution',
                'title' => 'Weekly Community Meal Drive — Pune',
                'short_description' => 'Distributed hot, nutritious meals to over 300 families in the Pune slum communities.',
                'full_description' => "Every Sunday, our volunteers gather to prepare and distribute hot meals to families facing food insecurity.\n\nThis week's drive reached over 300 families across three neighborhoods, with support from local kitchen partners and a growing team of dedicated volunteers.",
                'activity_date' => now()->subDays(10)->toDateString(),
                'location' => 'Yerawada, Pune',
                'organizer' => 'Gauri Ganesh Seva Sanstha',
                'status' => 'published',
                'is_featured' => true,
            ],
            [
                'category' => 'Medical Camps',
                'title' => 'Free Health Checkup Camp',
                'short_description' => 'Free general health checkups, eye tests, and medicine distribution for underserved families.',
                'full_description' => "In partnership with local doctors and volunteer nurses, we organized a free health camp offering general checkups, eye screenings, and basic medicine distribution.\n\nOver 200 people were screened, with follow-up referrals arranged for those needing specialist care.",
                'activity_date' => now()->subDays(25)->toDateString(),
                'location' => 'Community Hall, Hadapsar',
                'organizer' => 'Gauri Ganesh Seva Sanstha Medical Wing',
                'status' => 'published',
                'is_featured' => true,
            ],
            [
                'category' => 'Blood Donation',
                'title' => 'Annual Blood Donation Camp',
                'short_description' => 'Collected over 150 units of blood in partnership with the city blood bank.',
                'full_description' => "Our annual blood donation drive brought together volunteers, donors, and medical staff from the city blood bank.\n\nWe collected over 150 units of blood, which will support emergency transfusions across partner hospitals.",
                'activity_date' => now()->subDays(40)->toDateString(),
                'location' => 'GGSS Community Center',
                'organizer' => 'Gauri Ganesh Seva Sanstha',
                'status' => 'published',
                'is_featured' => false,
            ],
            [
                'category' => 'Education Support',
                'title' => 'School Supplies Distribution Drive',
                'short_description' => 'Provided notebooks, uniforms, and school bags to 120 children ahead of the new academic year.',
                'full_description' => "As the new academic year began, we distributed notebooks, uniforms, and school bags to 120 children from families who otherwise could not afford them.\n\nThis initiative aims to reduce the financial burden on parents and keep children in school.",
                'activity_date' => now()->subDays(60)->toDateString(),
                'location' => 'Various Government Schools, Pune',
                'organizer' => 'Gauri Ganesh Seva Sanstha Education Wing',
                'status' => 'published',
                'is_featured' => false,
            ],
            [
                'category' => 'Women Empowerment',
                'title' => 'Tailoring & Skill Development Workshop',
                'short_description' => 'A 4-week tailoring workshop empowering women with a path to financial independence.',
                'full_description' => "This four-week workshop trained 25 women in tailoring and basic garment design, giving them practical skills toward financial independence.\n\nParticipants received certificates and starter kits upon completion.",
                'activity_date' => now()->subDays(15)->toDateString(),
                'location' => 'GGSS Community Center',
                'organizer' => 'Gauri Ganesh Seva Sanstha',
                'status' => 'published',
                'is_featured' => false,
            ],
            [
                'category' => 'Tree Plantation',
                'title' => 'Green Pune Tree Plantation Drive',
                'short_description' => 'Planted over 500 saplings along the riverside with local volunteers and schoolchildren.',
                'full_description' => "In collaboration with local schools and civic volunteers, we planted over 500 native saplings along the riverside as part of our ongoing environmental initiative.\n\nVolunteers have committed to maintaining and watering the saplings for the next year.",
                'activity_date' => now()->subDays(5)->toDateString(),
                'location' => 'Riverside Park, Pune',
                'organizer' => 'Gauri Ganesh Seva Sanstha Green Team',
                'status' => 'published',
                'is_featured' => true,
            ],
            [
                'category' => 'Disaster Relief',
                'title' => 'Flood Relief Kit Distribution',
                'short_description' => 'Emergency relief kits with food, clothing, and medicine delivered to flood-affected families.',
                'full_description' => 'Following heavy monsoon flooding, our rapid-response team delivered emergency relief kits containing food, clothing, and basic medicines to over 80 displaced families.',
                'activity_date' => now()->subDays(90)->toDateString(),
                'location' => 'Kolhapur District',
                'organizer' => 'Gauri Ganesh Seva Sanstha',
                'status' => 'published',
                'is_featured' => false,
            ],
            [
                'category' => 'Animal Welfare',
                'title' => 'Stray Animal Feeding & Vaccination Drive',
                'short_description' => 'Fed and vaccinated over 60 stray animals in partnership with a local veterinary clinic.',
                'full_description' => 'Working alongside a local veterinary clinic, our volunteers fed and vaccinated over 60 stray dogs and cats across the city, helping curb disease and support animal welfare.',
                'activity_date' => now()->subDays(3)->toDateString(),
                'location' => 'Multiple locations, Pune',
                'organizer' => 'Gauri Ganesh Seva Sanstha Animal Welfare Cell',
                'status' => 'draft',
                'is_featured' => false,
            ],
            [
                'category' => 'Clothes Distribution',
                'title' => 'Winter Clothes Collection & Distribution',
                'short_description' => 'Collected and distributed warm clothing to families ahead of the winter season.',
                'full_description' => "Ahead of the winter season, we ran a city-wide collection of warm clothing and blankets, distributing them to over 200 families in need across Pune's outskirts.",
                'activity_date' => now()->addDays(20)->toDateString(),
                'location' => 'Pune Outskirts',
                'organizer' => 'Gauri Ganesh Seva Sanstha',
                'status' => 'draft',
                'is_featured' => false,
            ],
        ];

        foreach ($activities as $data) {
            $category = ActivityCategory::where('name', $data['category'])->first();

            Activity::updateOrCreate(
                ['title' => $data['title']],
                [
                    'slug' => Str::slug($data['title']),
                    'activity_category_id' => $category?->id,
                    'short_description' => $data['short_description'],
                    'full_description' => $data['full_description'],
                    'activity_date' => $data['activity_date'],
                    'location' => $data['location'],
                    'organizer' => $data['organizer'],
                    'status' => $data['status'],
                    'is_featured' => $data['is_featured'],
                ]
            );
        }
    }
}
