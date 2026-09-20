<?php

namespace Database\Seeders;

use App\Enums\TestimonialStatus;
use App\Enums\TestimonialType;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class TestimonialsSeeder extends Seeder
{
    /**
     * Demo testimonials so every public view has content to render while
     * developing. Testimonials are personal statements, so this seeder
     * deliberately refuses to run in production — real, consented entries
     * must be added through the admin panel there.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('TestimonialsSeeder skipped: demo testimonials are never seeded in production.');

            return;
        }

        if (Testimonial::query()->exists()) {
            return;
        }

        $testimonials = [
            [
                'name' => 'Sunita Jadhav',
                'designation' => 'Mother of two',
                'location' => 'Hadapsar, Pune',
                'type' => TestimonialType::Beneficiary,
                'content' => 'When my younger son fell ill, we had no way to pay for tests. The free medical camp gave my family care we could not have afforded otherwise, and the volunteers followed up for weeks afterwards. I will never forget that kindness.',
                'rating' => 5,
                'is_featured' => true,
                'display_order' => 1,
            ],
            [
                'name' => 'Anita Sharma',
                'designation' => 'Monthly Donor',
                'location' => 'Mumbai',
                'type' => TestimonialType::Donor,
                'content' => 'Seeing the impact update after my donation made all the difference. I know exactly which drive my contribution supported and how many families it reached — that transparency is why I keep giving every month.',
                'rating' => 5,
                'is_featured' => true,
                'display_order' => 2,
            ],
            [
                'name' => 'Rohit Deshmukh',
                'designation' => 'Volunteer since 2024',
                'location' => 'Pune',
                'type' => TestimonialType::Volunteer,
                'content' => "Volunteering here showed me what real, consistent seva looks like. It isn't one big event — it's showing up every Sunday, learning the names of the families we serve, and doing the small things well.",
                'rating' => 5,
                'is_featured' => true,
                'display_order' => 3,
            ],
            [
                'name' => 'Meera Kulkarni',
                'designation' => 'CSR Lead',
                'organization' => 'Sunrise CSR Trust',
                'location' => 'Pune',
                'type' => TestimonialType::Partner,
                'content' => 'We have partnered with the Sanstha on three education drives. Every rupee is accounted for, reports arrive on time, and the team on the ground is deeply committed. They are a partner we trust completely.',
                'rating' => null,
                'is_featured' => false,
                'display_order' => 4,
            ],
            [
                'name' => 'Ramesh Pawar',
                'designation' => 'Shopkeeper',
                'location' => 'Yerawada, Pune',
                'type' => TestimonialType::CommunityMember,
                'content' => 'The winter clothing distribution in our lane reached the elderly people nobody else thinks about. The volunteers were respectful and patient with everyone. Our neighbourhood is grateful.',
                'rating' => 4,
                'is_featured' => false,
                'display_order' => 5,
            ],
            [
                'name' => 'Priya Nair',
                'designation' => 'First-time Donor',
                'location' => 'Bengaluru',
                'type' => TestimonialType::Donor,
                'content' => 'I donated to the school kits campaign on a whim and received a thank-you note with photos of the handover a week later. It felt personal, not transactional. Highly recommend supporting their work.',
                'rating' => 5,
                'is_featured' => false,
                'display_order' => 6,
            ],
            [
                'name' => 'Kavita Bhosale',
                'designation' => 'Student, Class 10',
                'location' => 'Pune',
                'type' => TestimonialType::Beneficiary,
                'content' => 'The study kit and the evening tuition helped me pass my exams this year. My parents could not have paid for classes. Thank you for believing in students like me.',
                'rating' => 5,
                'is_featured' => false,
                'display_order' => 7,
                // Awaiting consent — must stay off the public site.
                'status' => TestimonialStatus::PendingReview,
                'consent_given' => false,
            ],
        ];

        foreach ($testimonials as $data) {
            $status = $data['status'] ?? TestimonialStatus::Published;
            $consented = $data['consent_given'] ?? true;

            Testimonial::create([
                'name' => $data['name'],
                'designation' => $data['designation'] ?? null,
                'organization' => $data['organization'] ?? null,
                'location' => $data['location'] ?? null,
                'content' => $data['content'],
                'rating' => $data['rating'],
                'type' => $data['type']->value,
                'status' => $status->value,
                'is_featured' => $data['is_featured'],
                'display_order' => $data['display_order'],
                'published_at' => $status === TestimonialStatus::Published ? now()->subDays($data['display_order']) : null,
                'consent_given' => $consented,
                'consented_at' => $consented ? now()->subDays($data['display_order'] + 3) : null,
                'admin_notes' => 'Demo seed data — replace with real, consented testimonials before launch.',
            ]);
        }
    }
}
