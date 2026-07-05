<?php

namespace Database\Seeders;

use App\Models\DonationCampaign;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DonationCampaignsSeeder extends Seeder
{
    /**
     * Demo campaigns — editable and extendable from the admin CMS.
     */
    public function run(): void
    {
        $campaigns = [
            [
                'name' => 'Food Distribution',
                'short_description' => 'Hot, nutritious meals for families facing food insecurity across Pune.',
                'full_description' => "Hunger is still a daily reality for many families in our city.\n\nYour donation funds weekly community meal drives, grocery kits for families in crisis, and nutrition support for children and the elderly. Just ₹501 feeds a family of four for a week.",
                'goal_amount' => 500000,
                'status' => 'active',
                'is_featured' => true,
                'order_column' => 1,
            ],
            [
                'name' => 'Blanket Distribution',
                'short_description' => 'Warm blankets for homeless families and street dwellers before winter.',
                'full_description' => "Every winter, hundreds of people in our city sleep in the open without protection from the cold.\n\nEach ₹350 donation provides one thick, durable blanket. Our volunteers distribute them directly on night rounds through the coldest weeks of the year.",
                'goal_amount' => 150000,
                'status' => 'active',
                'is_featured' => false,
                'order_column' => 2,
            ],
            [
                'name' => 'Medical Assistance',
                'short_description' => 'Free health camps, medicines, and emergency treatment support for the underserved.',
                'full_description' => "Quality healthcare remains out of reach for many daily-wage families.\n\nYour support funds free health checkup camps, essential medicines, diagnostic tests, and emergency treatment assistance for patients who cannot afford care.",
                'goal_amount' => 750000,
                'status' => 'active',
                'is_featured' => true,
                'order_column' => 3,
            ],
            [
                'name' => 'Education Support',
                'short_description' => 'School fees, supplies, and coaching for children from low-income families.',
                'full_description' => "Education is the surest path out of poverty — but school fees, books, and uniforms put it beyond many families' reach.\n\n₹2,100 sponsors a child's school supplies for a full academic year. Larger gifts help cover fees and after-school coaching.",
                'goal_amount' => 600000,
                'status' => 'active',
                'is_featured' => true,
                'order_column' => 4,
            ],
            [
                'name' => 'Temple Service',
                'short_description' => 'Support daily seva, annadanam, and upkeep of temple community programs.',
                'full_description' => "Our temple seva programs combine devotion with service — daily annadanam for devotees and the needy, festival community meals, and upkeep of shared spaces.\n\nContributions of any size support this ongoing seva.",
                'goal_amount' => null,
                'status' => 'active',
                'is_featured' => false,
                'order_column' => 5,
            ],
            [
                'name' => 'General Donation',
                'short_description' => 'Let us direct your gift to wherever the need is greatest right now.',
                'full_description' => "Not sure which cause to pick? A general donation gives us the flexibility to respond quickly — whether that's a food shortage, a medical emergency, or a family in sudden crisis.\n\nEvery rupee is accounted for and reported in our annual impact statement.",
                'goal_amount' => null,
                'status' => 'active',
                'is_featured' => false,
                'order_column' => 6,
            ],
            [
                'name' => 'Emergency Relief',
                'short_description' => 'Rapid-response kits for families hit by floods, fires, and other disasters.',
                'full_description' => "When disaster strikes, the first 72 hours matter most.\n\nThis fund keeps ready stocks of food, clean water, clothing, and medicine so our rapid-response team can reach affected families immediately — as we did during the recent Kolhapur floods.",
                'goal_amount' => 300000,
                'status' => 'draft',
                'is_featured' => false,
                'order_column' => 7,
            ],
        ];

        foreach ($campaigns as $data) {
            DonationCampaign::updateOrCreate(
                ['name' => $data['name']],
                [
                    'slug' => Str::slug($data['name']),
                    'short_description' => $data['short_description'],
                    'full_description' => $data['full_description'],
                    'goal_amount' => $data['goal_amount'],
                    'currency' => 'INR',
                    'status' => $data['status'],
                    'is_featured' => $data['is_featured'],
                    'order_column' => $data['order_column'],
                ]
            );
        }
    }
}
