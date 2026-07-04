<?php

namespace Database\Seeders;

use App\Enums\AboutSectionKey;
use App\Models\AboutSection;
use Illuminate\Database\Seeder;

class AboutSectionsSeeder extends Seeder
{
    /**
     * Demo placeholder content per section — editable from the admin CMS
     * immediately after seeding.
     */
    public function run(): void
    {
        foreach (AboutSectionKey::orderedCases() as $order => $key) {
            $section = AboutSection::updateOrCreate(
                ['key' => $key->value],
                [
                    'name' => $key->label(),
                    'order_column' => $order,
                    'is_active' => $key !== AboutSectionKey::AwardsRecognition,
                    ...$this->content($key),
                ]
            );

            if ($section->wasRecentlyCreated) {
                $this->seedButtons($section, $key);
                $this->seedItems($section, $key);
            }
        }
    }

    /**
     * @return array<string, string|null>
     */
    private function content(AboutSectionKey $key): array
    {
        return match ($key) {
            AboutSectionKey::HeroBanner => [
                'heading' => 'About Us',
                'subheading' => 'Learn about our mission, our story, and the people behind Gauri Ganesh Seva Sanstha.',
                'description' => null,
            ],
            AboutSectionKey::OrgIntroduction => [
                'heading' => 'Who We Are',
                'subheading' => null,
                'description' => 'Gauri Ganesh Seva Sanstha is a registered trust dedicated to serving families in need through food, education, medical care, and social welfare programs.',
            ],
            AboutSectionKey::OurStory => [
                'heading' => 'Our Story',
                'subheading' => null,
                'description' => 'Founded with a simple belief that seva is action, not just intention, our journey began with a handful of volunteers distributing meals in local neighborhoods. Today, we run structured programs reaching hundreds of families every month.',
            ],
            AboutSectionKey::Mission => [
                'heading' => 'Our Mission',
                'subheading' => null,
                'description' => 'To restore dignity through food, education, medical care, and seva — reaching every family that needs a helping hand.',
            ],
            AboutSectionKey::Vision => [
                'heading' => 'Our Vision',
                'subheading' => null,
                'description' => 'A community where no one is left behind — where compassion, opportunity, and care are available to all, regardless of circumstance.',
            ],
            AboutSectionKey::CoreValues => [
                'heading' => 'Our Core Values',
                'subheading' => 'The principles that guide everything we do.',
                'description' => null,
            ],
            AboutSectionKey::Objectives => [
                'heading' => 'Our Objectives',
                'subheading' => 'What we set out to achieve.',
                'description' => null,
            ],
            AboutSectionKey::FounderMessage => [
                'heading' => 'A Message From Our Founder',
                'subheading' => 'Suresh Patil, Founder & Trustee',
                'description' => 'Every family we reach reminds us why this work matters. Seva is not charity — it is our shared responsibility to one another. Thank you for standing with us.',
            ],
            AboutSectionKey::JourneyTimeline => [
                'heading' => 'Our Journey',
                'subheading' => 'Key milestones along the way.',
                'description' => null,
            ],
            AboutSectionKey::WhyWeExist => [
                'heading' => 'Why We Exist',
                'subheading' => null,
                'description' => 'Because dignity should never depend on circumstance. We exist to close the gap between need and support — one meal, one classroom, one checkup at a time.',
            ],
            AboutSectionKey::ImpactHighlights => [
                'heading' => 'Our Impact',
                'subheading' => 'Every number represents a real family we have walked alongside.',
                'description' => null,
            ],
            AboutSectionKey::RegistrationLegal => [
                'heading' => 'Registration & Legal Information',
                'subheading' => null,
                'description' => 'Gauri Ganesh Seva Sanstha operates as a fully registered public charitable trust, compliant with Indian trust and tax regulations.',
            ],
            AboutSectionKey::TrustCertificates => [
                'heading' => 'Trust Certificates',
                'subheading' => null,
                'description' => 'Scanned copies of our registration and tax-exemption certificates are available below.',
            ],
            AboutSectionKey::AwardsRecognition => [
                'heading' => 'Awards & Recognition',
                'subheading' => 'Recognized for our commitment to community service.',
                'description' => null,
            ],
            AboutSectionKey::DonateCta => [
                'heading' => 'Ready to Make a Difference?',
                'subheading' => 'Your donation directly funds our food, education, and medical programs.',
                'description' => null,
            ],
            AboutSectionKey::VolunteerCta => [
                'heading' => 'Join Our Team of Volunteers',
                'subheading' => 'Give your time and skills to a cause that changes lives.',
                'description' => null,
            ],
        };
    }

    private function seedButtons(AboutSection $section, AboutSectionKey $key): void
    {
        $buttons = match ($key) {
            AboutSectionKey::DonateCta => [
                ['label' => 'Donate Now', 'url' => '/donate', 'variant' => 'accent'],
            ],
            AboutSectionKey::VolunteerCta => [
                ['label' => 'Become a Volunteer', 'url' => '/volunteer', 'variant' => 'secondary'],
            ],
            default => [],
        };

        foreach ($buttons as $order => $button) {
            $section->buttons()->create([...$button, 'order_column' => $order]);
        }
    }

    private function seedItems(AboutSection $section, AboutSectionKey $key): void
    {
        $items = match ($key) {
            AboutSectionKey::CoreValues => [
                ['title' => 'Compassion', 'icon' => 'heart', 'description' => 'We treat every person we serve with warmth, patience, and respect.'],
                ['title' => 'Integrity', 'icon' => 'shield-check', 'description' => 'We are honest and accountable in every commitment we make.'],
                ['title' => 'Transparency', 'icon' => 'document-check', 'description' => 'We openly share how every donation is used.'],
                ['title' => 'Community', 'icon' => 'users', 'description' => 'We believe lasting change happens when people work together.'],
            ],
            AboutSectionKey::Objectives => [
                ['title' => 'Provide Nutritious Meals', 'description' => 'Ensure no family in our reach goes without a meal.'],
                ['title' => 'Support Education Access', 'description' => 'Help children stay in school with supplies and mentoring.'],
                ['title' => 'Deliver Medical Care', 'description' => 'Run free health camps for underserved communities.'],
                ['title' => 'Strengthen Community Welfare', 'description' => 'Support elders, widows, and the differently-abled.'],
            ],
            AboutSectionKey::JourneyTimeline => [
                ['title' => '2015', 'subtitle' => 'Founded', 'description' => 'Gauri Ganesh Seva Sanstha was founded by a small group of volunteers.'],
                ['title' => '2018', 'subtitle' => 'First Medical Camp', 'description' => 'Our first free health checkup camp served over 200 families.'],
                ['title' => '2021', 'subtitle' => '100th Food Drive', 'description' => 'We completed our 100th community food distribution drive.'],
                ['title' => '2024', 'subtitle' => 'Community Center Opened', 'description' => 'Opened a permanent center for ongoing programs and volunteer training.'],
            ],
            AboutSectionKey::ImpactHighlights => [
                ['title' => '12,500+', 'subtitle' => 'Meals Served'],
                ['title' => '850+', 'subtitle' => 'Families Supported'],
                ['title' => '120+', 'subtitle' => 'Active Volunteers'],
                ['title' => '8+', 'subtitle' => 'Years of Service'],
            ],
            AboutSectionKey::AwardsRecognition => [
                ['title' => 'Community Service Excellence Award', 'subtitle' => '2023 — Pune Rotary Club', 'description' => 'Recognized for outstanding contribution to community welfare.'],
            ],
            default => [],
        };

        foreach ($items as $order => $item) {
            $section->items()->create([
                'title' => $item['title'],
                'subtitle' => $item['subtitle'] ?? null,
                'description' => $item['description'] ?? null,
                'icon' => $item['icon'] ?? null,
                'link_url' => $item['link_url'] ?? null,
                'is_active' => true,
                'order_column' => $order,
            ]);
        }
    }
}
