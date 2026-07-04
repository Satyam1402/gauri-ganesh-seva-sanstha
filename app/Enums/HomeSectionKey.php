<?php

namespace App\Enums;

enum HomeSectionKey: string
{
    case HeroBanner = 'hero_banner';
    case AboutPreview = 'about_preview';
    case Mission = 'mission';
    case Vision = 'vision';
    case OurActivities = 'our_activities';
    case WhyChooseUs = 'why_choose_us';
    case ImpactStatistics = 'impact_statistics';
    case DonationCta = 'donation_cta';
    case FeaturedCampaigns = 'featured_campaigns';
    case UpcomingEvents = 'upcoming_events';
    case LatestNews = 'latest_news';
    case VolunteerSection = 'volunteer_section';
    case Testimonials = 'testimonials';
    case Partners = 'partners';
    case GalleryPreview = 'gallery_preview';
    case FaqPreview = 'faq_preview';
    case Newsletter = 'newsletter';
    case ContactCta = 'contact_cta';

    public function label(): string
    {
        return match ($this) {
            self::HeroBanner => 'Hero Banner',
            self::AboutPreview => 'About Preview',
            self::Mission => 'Mission',
            self::Vision => 'Vision',
            self::OurActivities => 'Our Activities',
            self::WhyChooseUs => 'Why Choose Us',
            self::ImpactStatistics => 'Impact Statistics',
            self::DonationCta => 'Donation CTA',
            self::FeaturedCampaigns => 'Featured Campaigns',
            self::UpcomingEvents => 'Upcoming Events',
            self::LatestNews => 'Latest News',
            self::VolunteerSection => 'Volunteer Section',
            self::Testimonials => 'Testimonials',
            self::Partners => 'Partners',
            self::GalleryPreview => 'Gallery Preview',
            self::FaqPreview => 'FAQ Preview',
            self::Newsletter => 'Newsletter',
            self::ContactCta => 'Contact CTA',
        };
    }

    /**
     * Whether the admin form for this section should expose the buttons repeater.
     */
    public function supportsButtons(): bool
    {
        return match ($this) {
            self::Mission, self::Vision, self::WhyChooseUs, self::ImpactStatistics,
            self::Testimonials, self::Partners, self::Newsletter => false,
            default => true,
        };
    }

    /**
     * Whether the admin form for this section should expose the repeatable items list.
     */
    public function supportsItems(): bool
    {
        return match ($this) {
            self::OurActivities, self::WhyChooseUs, self::ImpactStatistics,
            self::FeaturedCampaigns, self::UpcomingEvents, self::LatestNews,
            self::Testimonials, self::Partners, self::GalleryPreview, self::FaqPreview => true,
            default => false,
        };
    }

    /**
     * Canonical default display order, matching the product's section list.
     *
     * @return list<self>
     */
    public static function orderedCases(): array
    {
        return [
            self::HeroBanner,
            self::AboutPreview,
            self::Mission,
            self::Vision,
            self::OurActivities,
            self::WhyChooseUs,
            self::ImpactStatistics,
            self::DonationCta,
            self::FeaturedCampaigns,
            self::UpcomingEvents,
            self::LatestNews,
            self::VolunteerSection,
            self::Testimonials,
            self::Partners,
            self::GalleryPreview,
            self::FaqPreview,
            self::Newsletter,
            self::ContactCta,
        ];
    }
}
