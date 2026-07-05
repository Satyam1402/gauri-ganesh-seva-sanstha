<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EventsSeeder extends Seeder
{
    /**
     * Demo events — a mix of upcoming (some with registration) and past
     * events so every public view has content to render.
     */
    public function run(): void
    {
        $events = [
            [
                'category' => 'Medical Camp',
                'title' => 'Mega Free Health Checkup Camp 2026',
                'short_description' => 'Free general checkups, eye tests, blood sugar screening, and medicine distribution — open to all.',
                'full_description' => "Join us for our biggest health camp of the year, run in partnership with local hospitals and volunteer doctors.\n\nServices include general health checkups, eye screening, blood pressure and diabetes tests, and free basic medicines. Specialist referrals will be arranged where needed.\n\nNo appointment necessary, but registering below helps us plan supplies.",
                'start_date' => now()->addDays(20)->toDateString(),
                'start_time' => '09:00',
                'end_time' => '17:00',
                'venue' => 'GGSS Community Center',
                'address' => '123 Seva Marg, Hadapsar',
                'city' => 'Pune',
                'state' => 'Maharashtra',
                'organizer' => 'Gauri Ganesh Seva Sanstha Medical Wing',
                'max_participants' => 300,
                'requires_registration' => true,
                'status' => 'published',
                'is_featured' => true,
            ],
            [
                'category' => 'Blood Donation Camp',
                'title' => 'Blood Donation Drive — Save Three Lives',
                'short_description' => 'One donation can save up to three lives. Join our quarterly blood donation camp.',
                'full_description' => "Our quarterly blood donation camp, organised with the city blood bank.\n\nAll donors receive a certificate, refreshments, and a mini health report. Donation takes about 30 minutes end to end.\n\nDonors must be 18–65 years old and weigh at least 50 kg.",
                'start_date' => now()->addDays(35)->toDateString(),
                'start_time' => '10:00',
                'end_time' => '16:00',
                'venue' => 'Community Hall',
                'address' => 'Station Road, Yerawada',
                'city' => 'Pune',
                'state' => 'Maharashtra',
                'organizer' => 'Gauri Ganesh Seva Sanstha',
                'max_participants' => 150,
                'requires_registration' => true,
                'status' => 'published',
                'is_featured' => true,
            ],
            [
                'category' => 'Tree Plantation',
                'title' => 'Monsoon Tree Plantation Drive',
                'short_description' => 'Help us plant 1,000 native saplings along the riverside this monsoon.',
                'full_description' => "Our annual monsoon plantation drive returns — this year with a target of 1,000 native saplings.\n\nSaplings, tools, and refreshments are provided. Wear comfortable clothes you don't mind getting muddy!\n\nFamilies and children are welcome; every registered volunteer receives a participation certificate.",
                'start_date' => now()->addDays(50)->toDateString(),
                'end_date' => now()->addDays(51)->toDateString(),
                'start_time' => '07:00',
                'end_time' => '11:00',
                'venue' => 'Riverside Park',
                'address' => 'River Road',
                'city' => 'Pune',
                'state' => 'Maharashtra',
                'organizer' => 'GGSS Green Team',
                'requires_registration' => true,
                'status' => 'published',
                'is_featured' => true,
            ],
            [
                'category' => 'Food Distribution',
                'title' => 'Sunday Community Meal Drive',
                'short_description' => 'Weekly hot meal distribution for families in need — volunteers always welcome.',
                'full_description' => "Every Sunday our kitchen team prepares and serves hot, nutritious meals for over 300 families.\n\nNo registration needed to attend — just come along. Volunteers who want to help cook or serve can reach us through the volunteer page.",
                'start_date' => now()->addDays(6)->toDateString(),
                'start_time' => '11:00',
                'end_time' => '14:00',
                'venue' => 'GGSS Community Center',
                'city' => 'Pune',
                'state' => 'Maharashtra',
                'organizer' => 'Gauri Ganesh Seva Sanstha',
                'requires_registration' => false,
                'status' => 'published',
                'is_featured' => false,
            ],
            [
                'category' => 'Awareness Campaign',
                'title' => 'Digital Literacy Awareness Workshop',
                'short_description' => 'A free workshop teaching seniors how to use smartphones, UPI, and government apps safely.',
                'full_description' => "Technology should not leave anyone behind. This free workshop helps senior citizens learn smartphone basics, safe UPI payments, and how to access government services online.\n\nSeats are limited so registration is required. Bring your own smartphone if you have one.",
                'start_date' => now()->addDays(12)->toDateString(),
                'start_time' => '15:00',
                'end_time' => '18:00',
                'venue' => 'GGSS Community Center',
                'city' => 'Pune',
                'state' => 'Maharashtra',
                'organizer' => 'GGSS Education Wing',
                'max_participants' => 40,
                'requires_registration' => true,
                'status' => 'published',
                'is_featured' => false,
            ],
            [
                'category' => 'Festival Celebration',
                'title' => 'Ganesh Chaturthi Community Celebration',
                'short_description' => 'Aarti, prasad, and cultural programs — celebrate Ganesh Chaturthi with the whole community.',
                'full_description' => "Our flagship annual celebration with aarti, prasad distribution, rangoli competition, and cultural performances by local children.\n\nEveryone is welcome — bring your family and friends.",
                'start_date' => now()->subDays(45)->toDateString(),
                'start_time' => '17:00',
                'end_time' => '21:00',
                'venue' => 'GGSS Community Center',
                'city' => 'Pune',
                'state' => 'Maharashtra',
                'organizer' => 'Gauri Ganesh Seva Sanstha',
                'requires_registration' => false,
                'status' => 'completed',
                'is_featured' => false,
            ],
            [
                'category' => 'Blanket Distribution',
                'title' => 'Winter Blanket Distribution 2025',
                'short_description' => 'Distributed 500 blankets to homeless families ahead of the winter cold wave.',
                'full_description' => "Ahead of last winter's cold wave, our volunteers distributed 500 warm blankets across the city's pavement-dwelling communities.\n\nThank you to every donor who made this possible.",
                'start_date' => now()->subDays(190)->toDateString(),
                'venue' => 'Multiple locations',
                'city' => 'Pune',
                'state' => 'Maharashtra',
                'organizer' => 'Gauri Ganesh Seva Sanstha',
                'requires_registration' => false,
                'status' => 'completed',
                'is_featured' => false,
            ],
            [
                'category' => 'Volunteer Meeting',
                'title' => 'Quarterly Volunteer Meetup',
                'short_description' => 'Planning session for next quarter — all registered volunteers are invited.',
                'full_description' => 'Quarterly planning and appreciation meetup for our volunteer network. Agenda: last quarter review, upcoming event calendar, and team assignments.',
                'start_date' => now()->addDays(8)->toDateString(),
                'start_time' => '18:00',
                'end_time' => '20:00',
                'venue' => 'GGSS Community Center',
                'city' => 'Pune',
                'state' => 'Maharashtra',
                'organizer' => 'Gauri Ganesh Seva Sanstha',
                'requires_registration' => false,
                'status' => 'draft',
                'is_featured' => false,
            ],
        ];

        foreach ($events as $data) {
            $category = EventCategory::where('name', $data['category'])->first();

            Event::updateOrCreate(
                ['slug' => Str::slug($data['title'])],
                [
                    'event_category_id' => $category?->id,
                    'title' => $data['title'],
                    'short_description' => $data['short_description'],
                    'full_description' => $data['full_description'],
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'] ?? null,
                    'start_time' => $data['start_time'] ?? null,
                    'end_time' => $data['end_time'] ?? null,
                    'venue' => $data['venue'] ?? null,
                    'address' => $data['address'] ?? null,
                    'city' => $data['city'] ?? null,
                    'state' => $data['state'] ?? null,
                    'organizer' => $data['organizer'] ?? null,
                    'max_participants' => $data['max_participants'] ?? null,
                    'requires_registration' => $data['requires_registration'],
                    'status' => $data['status'],
                    'is_featured' => $data['is_featured'],
                ]
            );
        }
    }
}
