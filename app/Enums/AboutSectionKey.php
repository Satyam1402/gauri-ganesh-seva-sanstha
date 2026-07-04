<?php

namespace App\Enums;

enum AboutSectionKey: string
{
    case HeroBanner = 'hero_banner';
    case OrgIntroduction = 'org_introduction';
    case OurStory = 'our_story';
    case Mission = 'mission';
    case Vision = 'vision';
    case CoreValues = 'core_values';
    case Objectives = 'objectives';
    case FounderMessage = 'founder_message';
    case JourneyTimeline = 'journey_timeline';
    case WhyWeExist = 'why_we_exist';
    case ImpactHighlights = 'impact_highlights';
    case RegistrationLegal = 'registration_legal';
    case TrustCertificates = 'trust_certificates';
    case AwardsRecognition = 'awards_recognition';
    case DonateCta = 'donate_cta';
    case VolunteerCta = 'volunteer_cta';

    public function label(): string
    {
        return match ($this) {
            self::HeroBanner => 'Hero Banner',
            self::OrgIntroduction => 'Organization Introduction',
            self::OurStory => 'Our Story',
            self::Mission => 'Mission',
            self::Vision => 'Vision',
            self::CoreValues => 'Core Values',
            self::Objectives => 'Objectives',
            self::FounderMessage => 'Founder / President Message',
            self::JourneyTimeline => 'Our Journey Timeline',
            self::WhyWeExist => 'Why We Exist',
            self::ImpactHighlights => 'Impact Highlights',
            self::RegistrationLegal => 'Registration & Legal Information',
            self::TrustCertificates => 'Trust Certificates',
            self::AwardsRecognition => 'Awards & Recognition',
            self::DonateCta => 'CTA to Donate',
            self::VolunteerCta => 'CTA to Volunteer',
        };
    }

    /**
     * Whether the admin form for this section should expose the buttons repeater.
     */
    public function supportsButtons(): bool
    {
        return match ($this) {
            self::DonateCta, self::VolunteerCta => true,
            default => false,
        };
    }

    /**
     * Whether the admin form for this section should expose the repeatable items list.
     */
    public function supportsItems(): bool
    {
        return match ($this) {
            self::CoreValues, self::Objectives, self::JourneyTimeline, self::AwardsRecognition => true,
            default => false,
        };
    }

    /**
     * Sections whose public content is sourced from OrgProfile rather than
     * the generic heading/description/items fields.
     */
    public function usesOrgProfile(): bool
    {
        return match ($this) {
            self::RegistrationLegal, self::TrustCertificates => true,
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
            self::OrgIntroduction,
            self::OurStory,
            self::Mission,
            self::Vision,
            self::CoreValues,
            self::Objectives,
            self::FounderMessage,
            self::JourneyTimeline,
            self::WhyWeExist,
            self::ImpactHighlights,
            self::RegistrationLegal,
            self::TrustCertificates,
            self::AwardsRecognition,
            self::DonateCta,
            self::VolunteerCta,
        ];
    }
}
