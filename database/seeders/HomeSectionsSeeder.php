<?php

namespace Database\Seeders;

use App\Enums\HomeSectionKey;
use App\Models\HomeSection;
use Illuminate\Database\Seeder;

class HomeSectionsSeeder extends Seeder
{
    /**
     * Demo placeholder content per section — editable from the admin CMS
     * immediately after seeding. Copy mirrors docs/UI-UX-BLUEPRINT.md Home Page.
     */
    public function run(): void
    {
        foreach (HomeSectionKey::orderedCases() as $order => $key) {
            $section = HomeSection::updateOrCreate(
                ['key' => $key->value],
                [
                    'name' => $key->label(),
                    'order_column' => $order,
                    'is_active' => true,
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
    private function content(HomeSectionKey $key): array
    {
        return match ($key) {
            HomeSectionKey::HeroBanner => [
                'heading' => 'Together, We Restore Dignity.',
                'subheading' => 'Gauri Ganesh Seva Sanstha provides food, education, medical care, and hope to families in need — because seva is action, not just intention.',
                'description' => null,
            ],
            HomeSectionKey::AboutPreview => [
                'heading' => 'Who We Are',
                'subheading' => 'A trust-registered NGO serving our community with compassion.',
                'description' => 'Gauri Ganesh Seva Sanstha has spent years building trust through consistent, transparent seva — from food distribution drives to medical camps and education support for children who need it most.',
            ],
            HomeSectionKey::Mission => [
                'heading' => 'Our Mission',
                'subheading' => null,
                'description' => 'To restore dignity through food, education, medical care, and seva — reaching every family that needs a helping hand.',
            ],
            HomeSectionKey::Vision => [
                'heading' => 'Our Vision',
                'subheading' => null,
                'description' => 'A community where no one is left behind — where compassion, opportunity, and care are available to all, regardless of circumstance.',
            ],
            HomeSectionKey::OurActivities => [
                'heading' => 'How We Serve',
                'subheading' => 'Five core programs. One mission — to stand with those who need us most.',
                'description' => null,
            ],
            HomeSectionKey::WhyChooseUs => [
                'heading' => 'Why Choose Us',
                'subheading' => 'Trust and transparency in every rupee, every meal, every visit.',
                'description' => null,
            ],
            HomeSectionKey::ImpactStatistics => [
                'heading' => 'Our Impact So Far',
                'subheading' => 'Every number represents a real family, a real meal, a real moment of relief.',
                'description' => null,
            ],
            HomeSectionKey::DonationCta => [
                'heading' => 'Where Your Help Is Needed Most Right Now',
                'subheading' => 'Every contribution — big or small — funds a meal, a medicine, a moment of relief for a family in need.',
                'description' => null,
            ],
            HomeSectionKey::FeaturedCampaigns => [
                'heading' => 'Featured Campaigns',
                'subheading' => 'Specific, tangible ways your donation makes an immediate difference.',
                'description' => null,
            ],
            HomeSectionKey::UpcomingEvents => [
                'heading' => 'Upcoming Events',
                'subheading' => 'Join us at our next drive, camp, or community gathering.',
                'description' => null,
            ],
            HomeSectionKey::LatestNews => [
                'heading' => 'Latest Updates',
                'subheading' => 'Stories from the field — see what your support makes possible.',
                'description' => null,
            ],
            HomeSectionKey::VolunteerSection => [
                'heading' => 'Not Ready to Donate? Give Your Time Instead.',
                'subheading' => 'Volunteers are the heart of everything we do.',
                'description' => null,
            ],
            HomeSectionKey::Testimonials => [
                'heading' => 'Voices From Our Community',
                'subheading' => 'Trust, told by the people who have experienced it firsthand.',
                'description' => null,
            ],
            HomeSectionKey::Partners => [
                'heading' => 'Our Partners & Sponsors',
                'subheading' => 'Organizations who stand with us in service.',
                'description' => null,
            ],
            HomeSectionKey::GalleryPreview => [
                'heading' => 'Moments of Seva',
                'subheading' => 'A glimpse into our recent drives and community work.',
                'description' => null,
            ],
            HomeSectionKey::FaqPreview => [
                'heading' => 'Frequently Asked Questions',
                'subheading' => 'Quick answers for donors, volunteers, and those seeking help.',
                'description' => null,
            ],
            HomeSectionKey::Newsletter => [
                'heading' => 'Stay Updated',
                'subheading' => 'Subscribe for impact stories and updates from our work — no spam, ever.',
                'description' => null,
            ],
            HomeSectionKey::ContactCta => [
                'heading' => "Have Questions? We're Here to Help.",
                'subheading' => 'Reach out any time — our team responds within one business day.',
                'description' => null,
            ],
        };
    }

    private function seedButtons(HomeSection $section, HomeSectionKey $key): void
    {
        $buttons = match ($key) {
            HomeSectionKey::HeroBanner => [
                ['label' => 'Donate Now', 'url' => '/donate', 'variant' => 'accent'],
                ['label' => 'See Our Work', 'url' => '/about', 'variant' => 'secondary'],
            ],
            HomeSectionKey::AboutPreview => [
                ['label' => 'Learn More About Us', 'url' => '/about', 'variant' => 'ghost'],
            ],
            HomeSectionKey::OurActivities => [
                ['label' => 'View All Programs', 'url' => '/programs', 'variant' => 'ghost'],
            ],
            HomeSectionKey::DonationCta => [
                ['label' => 'Contribute Now', 'url' => '/donate', 'variant' => 'accent'],
            ],
            HomeSectionKey::FeaturedCampaigns => [
                ['label' => 'View All Campaigns', 'url' => '/campaigns', 'variant' => 'ghost'],
            ],
            HomeSectionKey::UpcomingEvents => [
                ['label' => 'View All Events', 'url' => '/events', 'variant' => 'ghost'],
            ],
            HomeSectionKey::LatestNews => [
                ['label' => 'Visit Our Blog', 'url' => '/blog', 'variant' => 'ghost'],
            ],
            HomeSectionKey::VolunteerSection => [
                ['label' => 'Become a Volunteer', 'url' => '/volunteer', 'variant' => 'secondary'],
            ],
            HomeSectionKey::GalleryPreview => [
                ['label' => 'View Full Gallery', 'url' => '/gallery', 'variant' => 'ghost'],
            ],
            HomeSectionKey::FaqPreview => [
                ['label' => 'See All FAQs', 'url' => '/faq', 'variant' => 'ghost'],
            ],
            HomeSectionKey::ContactCta => [
                ['label' => 'Contact Us', 'url' => '/contact', 'variant' => 'secondary'],
            ],
            default => [],
        };

        foreach ($buttons as $order => $button) {
            $section->buttons()->create([...$button, 'order_column' => $order]);
        }
    }

    private function seedItems(HomeSection $section, HomeSectionKey $key): void
    {
        $items = match ($key) {
            HomeSectionKey::OurActivities => [
                ['title' => 'Food Distribution', 'icon' => 'cake', 'description' => 'Regular meal and ration drives for families facing food insecurity.'],
                ['title' => 'Education Support', 'icon' => 'academic-cap', 'description' => 'School supplies, tuition support, and mentoring for children in need.'],
                ['title' => 'Medical Help', 'icon' => 'heart', 'description' => 'Free health checkup camps and medicine assistance for underserved families.'],
                ['title' => 'Clothes Distribution', 'icon' => 'sparkles', 'description' => 'Seasonal clothing drives reaching families before winter and festivals.'],
                ['title' => 'Social Welfare', 'icon' => 'users', 'description' => 'Community support programs for elders, widows, and the differently-abled.'],
            ],
            HomeSectionKey::WhyChooseUs => [
                ['title' => 'Registered Trust', 'icon' => 'shield-check', 'description' => 'Fully registered and compliant with Indian trust regulations.'],
                ['title' => '80G & 12A Certified', 'icon' => 'document-check', 'description' => 'Your donations are eligible for tax benefits under Indian law.'],
                ['title' => '100% Transparent Fund Usage', 'icon' => 'chart-bar', 'description' => 'Every rupee is tracked and reported back to our donor community.'],
                ['title' => 'Years of Seva', 'icon' => 'calendar', 'description' => 'A consistent track record of on-the-ground community service.'],
            ],
            HomeSectionKey::ImpactStatistics => [
                ['title' => '12,500+', 'subtitle' => 'Meals Served'],
                ['title' => '850+', 'subtitle' => 'Families Supported'],
                ['title' => '120+', 'subtitle' => 'Active Volunteers'],
                ['title' => '8+', 'subtitle' => 'Years of Service'],
            ],
            HomeSectionKey::FeaturedCampaigns => [
                ['title' => 'Ganesh Utsav Food Seva 2026', 'subtitle' => '₹1,87,500 raised of ₹5,00,000', 'description' => 'Meal packets and ration kits for families during the festival season.', 'link_url' => '/campaigns'],
                ['title' => 'Winter Clothing Drive', 'subtitle' => '₹42,000 raised of ₹1,50,000', 'description' => 'Warm clothing for children and elders ahead of winter.', 'link_url' => '/campaigns'],
                ['title' => 'School Kits for Kids', 'subtitle' => '₹65,000 raised of ₹2,00,000', 'description' => 'Books, uniforms, and supplies for the new school year.', 'link_url' => '/campaigns'],
            ],
            HomeSectionKey::UpcomingEvents => [
                ['title' => 'Free Health Checkup Camp', 'subtitle' => '20 Sep 2026 — Pune', 'description' => 'General health screening and free medicine distribution.', 'link_url' => '/events'],
                ['title' => 'Community Food Drive', 'subtitle' => '5 Oct 2026 — Pune', 'description' => 'Monthly ration distribution for registered families.', 'link_url' => '/events'],
                ['title' => 'Volunteer Orientation Day', 'subtitle' => '12 Oct 2026 — Pune', 'description' => 'Onboarding session for newly registered volunteers.', 'link_url' => '/events'],
            ],
            HomeSectionKey::LatestNews => [
                ['title' => 'How Your ₹500 Feeds a Family for a Week', 'subtitle' => 'Impact Stories', 'description' => 'A closer look at how donations translate into real meals.', 'link_url' => '/blog'],
                ['title' => 'Behind the Scenes of Our Medical Camps', 'subtitle' => 'Impact Stories', 'description' => 'Meet the volunteers and doctors making free checkups possible.', 'link_url' => '/blog'],
                ['title' => "2025's Winter Drive: By the Numbers", 'subtitle' => 'Announcements', 'description' => 'A recap of last winter\'s clothing distribution drive.', 'link_url' => '/blog'],
            ],
            HomeSectionKey::Testimonials => [
                ['title' => 'Anita Sharma', 'subtitle' => 'Donor', 'description' => 'Seeing the impact update after my donation made all the difference.'],
                ['title' => 'Rohit Deshmukh', 'subtitle' => 'Volunteer', 'description' => 'Volunteering here showed me what real, consistent seva looks like.'],
                ['title' => 'Sunita Jadhav', 'subtitle' => 'Beneficiary', 'description' => 'The medical camp gave my family care we could not have afforded otherwise.'],
            ],
            HomeSectionKey::Partners => [
                ['title' => 'ABC Foundation', 'link_url' => '#'],
                ['title' => 'Sunrise CSR Trust', 'link_url' => '#'],
                ['title' => 'Community Health Partners', 'link_url' => '#'],
            ],
            HomeSectionKey::GalleryPreview => [
                ['title' => 'Food distribution drive, Pune'],
                ['title' => 'Free medical checkup camp'],
                ['title' => 'Winter clothing distribution'],
                ['title' => 'School kits handover'],
                ['title' => 'Volunteer orientation day'],
                ['title' => 'Community welfare visit'],
            ],
            HomeSectionKey::FaqPreview => [
                ['title' => 'Is my donation tax-deductible?', 'description' => 'Yes, all donations are eligible for tax benefits under Section 80G.'],
                ['title' => 'How do I know my donation is used well?', 'description' => 'We publish transparent fund-usage reports and impact updates to every donor.'],
                ['title' => 'Can I volunteer without a long-term commitment?', 'description' => 'Yes — one-time and occasional volunteering opportunities are available.'],
                ['title' => 'How can I request help for my family?', 'description' => 'Use the Request Help form on our Contact page — all requests are kept confidential.'],
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
