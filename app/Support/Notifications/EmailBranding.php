<?php

namespace App\Support\Notifications;

use App\Http\Controllers\Frontend\LegalPageController;
use App\Services\SettingsService;

/**
 * Everything the shared email layout needs about the organisation, read
 * from Site Settings / the organisation profile (never hardcoded). Bound
 * to the `emails.layout` view through a view composer so every template —
 * including Laravel's password-reset mail — gets the same header/footer.
 */
class EmailBranding
{
    public function __construct(private SettingsService $settings) {}

    /**
     * Deliberately not memoised: queue workers are long-lived and the
     * settings map is already an in-memory/cached read, so re-resolving
     * per view keeps a running worker honest after settings change.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $name = (string) setting('general.site_name', config('app.name'));

        $address = setting('footer.address') ?? collect([
            setting('organization.address_line'),
            setting('organization.city'),
            setting('organization.state'),
            setting('organization.pin_code'),
        ])->filter()->implode(', ');

        $socials = collect([
            'Facebook' => setting('social.facebook_url'),
            'Instagram' => setting('social.instagram_url'),
            'YouTube' => setting('social.youtube_url'),
            'X' => setting('social.twitter_url'),
            'LinkedIn' => setting('social.linkedin_url'),
            'Telegram' => setting('social.telegram_url'),
        ])->filter()->all();

        $legal = [];

        foreach (LegalPageController::published($this->settings) as $slug => $title) {
            $legal[$title] = route('legal.show.'.$slug);
        }

        return [
            'name' => $name,
            'tagline' => setting('general.site_tagline'),
            'logo' => setting_media('branding.email_logo')
                ?? setting_media('branding.logo_light')
                ?? setting_media('branding.logo'),
            'email' => setting('footer.email') ?? setting('contact.email_primary'),
            'phone' => setting('footer.phone') ?? setting('contact.phone_primary'),
            'address' => $address !== '' ? $address : null,
            'website' => url('/'),
            'socials' => $socials,
            'legal' => $legal,
            'primaryColor' => setting('branding.primary_color') ?: '#8a3324',
            'year' => now()->format('Y'),
        ];
    }
}
