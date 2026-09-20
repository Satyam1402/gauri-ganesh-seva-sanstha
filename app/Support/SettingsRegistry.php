<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Declares every site setting: its group (admin tab), field type, label,
 * help text, validation rules and default. The admin form, the validator,
 * the seeder and the cached value map are all generated from this one
 * definition, so adding a setting is a single array entry.
 *
 * Groups with source = "org_profile" edit columns on the org_profiles
 * singleton instead of settings rows — that record already powers the
 * About and Contact pages, so duplicating it would create two sources of
 * truth.
 *
 * Nothing secret is ever declared here. Payment gateway secrets, SMTP
 * passwords and API keys live only in .env (see the Integrations group,
 * which stores public identifiers and merely *reports* whether the
 * matching env secret is configured).
 */
class SettingsRegistry
{
    public const SOURCE_SETTINGS = 'settings';

    public const SOURCE_ORG_PROFILE = 'org_profile';

    /**
     * Field types understood by the form engine and validator.
     */
    public const FIELD_TYPES = ['text', 'textarea', 'markdown', 'email', 'url', 'tel', 'select', 'boolean', 'integer', 'decimal', 'color', 'image', 'file', 'datetime', 'date'];

    private static ?array $groups = null;

    /**
     * @return array<string, array{label: string, description: string, source: string, fields: array<string, array<string, mixed>>}>
     */
    public static function groups(): array
    {
        return self::$groups ??= self::define();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function fields(string $group): array
    {
        return self::groups()[$group]['fields'] ?? [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function field(string $group, string $key): ?array
    {
        return self::groups()[$group]['fields'][$key] ?? null;
    }

    public static function hasGroup(string $group): bool
    {
        return array_key_exists($group, self::groups());
    }

    public static function source(string $group): string
    {
        return self::groups()[$group]['source'] ?? self::SOURCE_SETTINGS;
    }

    /**
     * Tab list in display order: [group => label].
     *
     * @return array<string, string>
     */
    public static function tabs(): array
    {
        return array_map(fn (array $g) => $g['label'], self::groups());
    }

    /**
     * Default values for every settings-table field, keyed "group.key".
     *
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        $defaults = [];

        foreach (self::groups() as $group => $definition) {
            foreach ($definition['fields'] as $key => $field) {
                $defaults["{$group}.{$key}"] = $field['default'] ?? null;
            }
        }

        return $defaults;
    }

    /**
     * Storage type in the settings table for a field type.
     */
    public static function storageType(string $fieldType): string
    {
        return match ($fieldType) {
            'boolean' => Setting::TYPE_BOOLEAN,
            'integer' => Setting::TYPE_INTEGER,
            'decimal' => Setting::TYPE_DECIMAL,
            'image', 'file' => Setting::TYPE_MEDIA,
            'textarea', 'markdown' => Setting::TYPE_TEXT,
            default => Setting::TYPE_STRING,
        };
    }

    public static function isMedia(array $field): bool
    {
        return in_array($field['type'], ['image', 'file'], true);
    }

    /**
     * @return array<string, array{label: string, description: string, source: string, fields: array<string, array<string, mixed>>}>
     */
    private static function define(): array
    {
        $phone = ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{7,20}$/'];
        $url = ['nullable', 'string', 'max:250', 'url:http,https'];

        return [
            'general' => [
                'label' => 'General',
                'description' => 'Website identity and locale defaults used across every page and email.',
                'source' => self::SOURCE_SETTINGS,
                'fields' => [
                    'site_name' => ['type' => 'text', 'label' => 'Website Name', 'help' => 'Shown in the browser title, header, footer and email subjects.', 'rules' => ['required', 'string', 'max:100'], 'default' => 'Gauri Ganesh Seva Sanstha'],
                    'site_tagline' => ['type' => 'text', 'label' => 'Tagline', 'help' => 'A short line used in social sharing previews.', 'rules' => ['nullable', 'string', 'max:150'], 'default' => 'Together, we restore dignity.'],
                    'site_description' => ['type' => 'textarea', 'label' => 'Website Description', 'help' => 'Default meta description for pages without their own.', 'rules' => ['nullable', 'string', 'max:300'], 'default' => 'Gauri Ganesh Seva Sanstha provides food, education, medical care, and hope to families in need.'],
                    'default_language' => ['type' => 'select', 'label' => 'Default Language', 'options' => ['en' => 'English', 'hi' => 'Hindi', 'mr' => 'Marathi'], 'rules' => ['required'], 'default' => 'en'],
                    'default_country' => ['type' => 'text', 'label' => 'Default Country', 'help' => 'Pre-filled on donation and volunteer forms.', 'rules' => ['nullable', 'string', 'max:100'], 'default' => 'India'],
                    'timezone' => ['type' => 'select', 'label' => 'Timezone', 'help' => 'Used when displaying dates and times.', 'options' => 'timezones', 'rules' => ['required', 'timezone:all'], 'default' => 'Asia/Kolkata'],
                    'currency' => ['type' => 'select', 'label' => 'Currency', 'options' => ['INR' => 'INR — Indian Rupee (₹)', 'USD' => 'USD — US Dollar ($)', 'EUR' => 'EUR — Euro (€)', 'GBP' => 'GBP — Pound Sterling (£)'], 'rules' => ['required'], 'default' => 'INR'],
                    'date_format' => ['type' => 'select', 'label' => 'Date Format', 'options' => ['d M Y' => '20 Sep 2026', 'd/m/Y' => '20/09/2026', 'M j, Y' => 'Sep 20, 2026', 'Y-m-d' => '2026-09-20'], 'rules' => ['required'], 'default' => 'd M Y'],
                ],
            ],

            'organization' => [
                'label' => 'Organization',
                'description' => 'Legal identity. Only fields you fill in are shown publicly — nothing is assumed about registrations or tax status.',
                'source' => self::SOURCE_ORG_PROFILE,
                'fields' => [
                    'legal_name' => ['type' => 'text', 'label' => 'Organization Name (legal)', 'rules' => ['nullable', 'string', 'max:200']],
                    'short_name' => ['type' => 'text', 'label' => 'Short Name', 'help' => 'e.g. GGSS', 'rules' => ['nullable', 'string', 'max:100']],
                    'registration_no' => ['type' => 'text', 'label' => 'Registration Number', 'help' => 'Trust / society registration number, if registered.', 'rules' => ['nullable', 'string', 'max:100']],
                    'registration_date' => ['type' => 'date', 'label' => 'Registration Date', 'rules' => ['nullable', 'date']],
                    'ngo_registration_no' => ['type' => 'text', 'label' => 'NGO Registration (NGO Darpan / other)', 'rules' => ['nullable', 'string', 'max:100']],
                    'pan_no' => ['type' => 'text', 'label' => 'PAN', 'rules' => ['nullable', 'string', 'max:20', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/i']],
                    'section_12a_no' => ['type' => 'text', 'label' => '12A Registration', 'help' => 'Leave blank unless the organisation holds a valid 12A registration.', 'rules' => ['nullable', 'string', 'max:100']],
                    'section_80g_no' => ['type' => 'text', 'label' => '80G Registration', 'help' => 'Leave blank unless the organisation holds a valid 80G certificate. Tax-deduction claims are only shown when this is filled in.', 'rules' => ['nullable', 'string', 'max:100']],
                    'trust_deed_no' => ['type' => 'text', 'label' => 'Trust Deed Number', 'rules' => ['nullable', 'string', 'max:100']],
                    'established_year' => ['type' => 'integer', 'label' => 'Established Year', 'rules' => ['nullable', 'integer', 'digits:4', 'min:1900', 'max:2100']],
                    'address_line' => ['type' => 'textarea', 'label' => 'Registered Address', 'rules' => ['nullable', 'string', 'max:500']],
                    'city' => ['type' => 'text', 'label' => 'City', 'rules' => ['nullable', 'string', 'max:100']],
                    'state' => ['type' => 'text', 'label' => 'State', 'rules' => ['nullable', 'string', 'max:100']],
                    'country' => ['type' => 'text', 'label' => 'Country', 'rules' => ['nullable', 'string', 'max:100']],
                    'pin_code' => ['type' => 'text', 'label' => 'PIN Code', 'rules' => ['nullable', 'string', 'max:12']],
                    'about_short' => ['type' => 'textarea', 'label' => 'About (short)', 'help' => 'One or two sentences used in the footer and sharing previews.', 'rules' => ['nullable', 'string', 'max:500']],
                ],
            ],

            'contact' => [
                'label' => 'Contact',
                'description' => 'Public contact details shown in the header, footer, Contact page and emails.',
                'source' => self::SOURCE_ORG_PROFILE,
                'fields' => [
                    'phone_primary' => ['type' => 'tel', 'label' => 'Primary Phone', 'rules' => $phone],
                    'phone_secondary' => ['type' => 'tel', 'label' => 'Secondary Phone', 'rules' => $phone],
                    'whatsapp_number' => ['type' => 'tel', 'label' => 'WhatsApp Number', 'help' => 'With country code, e.g. +91 98765 43210.', 'rules' => $phone],
                    'email_primary' => ['type' => 'email', 'label' => 'Primary Email', 'rules' => ['nullable', 'email:rfc', 'max:150']],
                    'email_secondary' => ['type' => 'email', 'label' => 'Secondary Email', 'rules' => ['nullable', 'email:rfc', 'max:150']],
                    'office_hours' => ['type' => 'text', 'label' => 'Office Hours', 'help' => 'e.g. Mon–Sat, 10:00 AM – 6:00 PM', 'rules' => ['nullable', 'string', 'max:200']],
                    'map_embed_url' => ['type' => 'url', 'label' => 'Google Maps URL', 'help' => 'A Google Maps share or embed link.', 'rules' => ['nullable', 'string', 'max:500', 'url:http,https']],
                    'emergency_phone' => ['type' => 'tel', 'label' => 'Emergency Contact', 'rules' => $phone],
                ],
            ],

            'social' => [
                'label' => 'Social Media',
                'description' => 'Profile links. Empty entries are simply not shown on the website.',
                'source' => self::SOURCE_ORG_PROFILE,
                'fields' => [
                    'facebook_url' => ['type' => 'url', 'label' => 'Facebook', 'rules' => $url],
                    'instagram_url' => ['type' => 'url', 'label' => 'Instagram', 'rules' => $url],
                    'youtube_url' => ['type' => 'url', 'label' => 'YouTube', 'rules' => $url],
                    'twitter_url' => ['type' => 'url', 'label' => 'X / Twitter', 'rules' => $url],
                    'linkedin_url' => ['type' => 'url', 'label' => 'LinkedIn', 'rules' => $url],
                    'telegram_url' => ['type' => 'url', 'label' => 'Telegram', 'rules' => $url],
                ],
            ],

            'branding' => [
                'label' => 'Branding',
                'description' => 'Logos, favicon and brand colours. Uploads use the site media library.',
                'source' => self::SOURCE_SETTINGS,
                'fields' => [
                    'logo' => ['type' => 'image', 'label' => 'Logo', 'help' => 'Main logo for the header. PNG with transparency recommended.'],
                    'logo_light' => ['type' => 'image', 'label' => 'Light Logo', 'help' => 'Used on dark backgrounds (footer). Falls back to the main logo.'],
                    'logo_dark' => ['type' => 'image', 'label' => 'Dark Logo', 'help' => 'Optional variant for dark mode.'],
                    'favicon' => ['type' => 'file', 'label' => 'Favicon', 'help' => 'PNG (recommended 512×512) or .ico.'],
                    'og_image' => ['type' => 'image', 'label' => 'Default Social Share Image', 'help' => 'Used when a page has no image of its own. 1200×630 recommended.'],
                    'email_logo' => ['type' => 'image', 'label' => 'Email Logo', 'help' => 'Shown at the top of outgoing emails. Falls back to the main logo.'],
                    'primary_color' => ['type' => 'color', 'label' => 'Primary Brand Colour', 'help' => 'Leave blank to keep the design-system default.', 'rules' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/']],
                    'secondary_color' => ['type' => 'color', 'label' => 'Secondary (Accent) Colour', 'help' => 'Used for donation buttons. Leave blank for the default.', 'rules' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/']],
                ],
            ],

            'donation' => [
                'label' => 'Donation',
                'description' => 'Offline payment details shown to donors. Gateway API keys are never stored here — they live only in the server .env file.',
                'source' => self::SOURCE_SETTINGS,
                'fields' => [
                    'upi_id' => ['type' => 'text', 'label' => 'UPI ID', 'help' => 'e.g. ggss@upi', 'rules' => ['nullable', 'string', 'max:100', 'regex:/^[\w.\-]{2,}@[a-zA-Z]{2,}$/']],
                    'upi_qr' => ['type' => 'image', 'label' => 'UPI QR Code', 'help' => 'Screenshot of your UPI QR code.'],
                    'bank_account_name' => ['type' => 'text', 'label' => 'Bank Account Name', 'rules' => ['nullable', 'string', 'max:150']],
                    'bank_name' => ['type' => 'text', 'label' => 'Bank Name', 'rules' => ['nullable', 'string', 'max:150']],
                    'account_number' => ['type' => 'text', 'label' => 'Account Number', 'rules' => ['nullable', 'string', 'max:30', 'regex:/^[0-9]{6,30}$/']],
                    'ifsc' => ['type' => 'text', 'label' => 'IFSC Code', 'rules' => ['nullable', 'string', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/i']],
                    'branch' => ['type' => 'text', 'label' => 'Branch', 'rules' => ['nullable', 'string', 'max:150']],
                    'instructions' => ['type' => 'markdown', 'label' => 'Donation Instructions', 'help' => 'Shown on the offline payment page. Markdown supported.', 'rules' => ['nullable', 'string', 'max:5000']],
                    'min_amount' => ['type' => 'decimal', 'label' => 'Minimum Donation Amount', 'rules' => ['required', 'numeric', 'min:1', 'max:1000000'], 'default' => 10],
                    'currency' => ['type' => 'select', 'label' => 'Donation Currency', 'options' => ['INR' => 'INR (₹)', 'USD' => 'USD ($)', 'EUR' => 'EUR (€)', 'GBP' => 'GBP (£)'], 'rules' => ['required'], 'default' => 'INR'],
                ],
            ],

            'footer' => [
                'label' => 'Footer',
                'description' => 'Footer text and call-to-action. Contact fields left blank fall back to the Contact tab.',
                'source' => self::SOURCE_SETTINGS,
                'fields' => [
                    'description' => ['type' => 'textarea', 'label' => 'Footer Description', 'rules' => ['nullable', 'string', 'max:300'], 'default' => 'Restoring dignity through food, education, medical care, and seva.'],
                    'copyright_text' => ['type' => 'text', 'label' => 'Copyright Text', 'help' => 'Use {year} and {name} as placeholders.', 'rules' => ['nullable', 'string', 'max:200'], 'default' => '© {year} {name}. All rights reserved.'],
                    'logo' => ['type' => 'image', 'label' => 'Footer Logo', 'help' => 'Falls back to the light logo, then the main logo.'],
                    'cta_heading' => ['type' => 'text', 'label' => 'Footer CTA Heading', 'rules' => ['nullable', 'string', 'max:120']],
                    'cta_text' => ['type' => 'textarea', 'label' => 'Footer CTA Text', 'rules' => ['nullable', 'string', 'max:300']],
                    'cta_button_label' => ['type' => 'text', 'label' => 'Footer CTA Button Label', 'rules' => ['nullable', 'string', 'max:40']],
                    'cta_button_url' => ['type' => 'text', 'label' => 'Footer CTA Button Link', 'help' => 'A site path like /donate or a full https:// address.', 'rules' => ['nullable', 'string', 'max:250', 'regex:#^(/[^\s]*|https?://[^\s]+)$#']],
                    'address' => ['type' => 'textarea', 'label' => 'Footer Address', 'help' => 'Leave blank to use the organisation address.', 'rules' => ['nullable', 'string', 'max:300']],
                    'phone' => ['type' => 'tel', 'label' => 'Footer Phone', 'help' => 'Leave blank to use the primary phone.', 'rules' => $phone],
                    'email' => ['type' => 'email', 'label' => 'Footer Email', 'help' => 'Leave blank to use the primary email.', 'rules' => ['nullable', 'email:rfc', 'max:150']],
                ],
            ],

            'navigation' => [
                'label' => 'Navigation',
                'description' => 'Header call-to-action buttons. Menu links themselves are managed on the Navigation Menu screen.',
                'source' => self::SOURCE_SETTINGS,
                'fields' => [
                    'header_cta_label' => ['type' => 'text', 'label' => 'Header Button Label', 'rules' => ['nullable', 'string', 'max:40'], 'default' => 'Donate Now'],
                    'header_cta_url' => ['type' => 'text', 'label' => 'Header Button Link', 'help' => 'A site path like /donate or a full https:// address.', 'rules' => ['nullable', 'string', 'max:250', 'regex:#^(/[^\s]*|https?://[^\s]+)$#'], 'default' => '/donate'],
                    'header_secondary_label' => ['type' => 'text', 'label' => 'Secondary Button Label', 'rules' => ['nullable', 'string', 'max:40'], 'default' => 'Get Involved'],
                    'header_secondary_url' => ['type' => 'text', 'label' => 'Secondary Button Link', 'rules' => ['nullable', 'string', 'max:250', 'regex:#^(/[^\s]*|https?://[^\s]+)$#'], 'default' => '/volunteer'],
                ],
            ],

            'legal' => [
                'label' => 'Legal',
                'description' => 'Enter only verified legal text. Pages are published when they contain content; empty pages return “not found” and their footer links are hidden.',
                'source' => self::SOURCE_SETTINGS,
                'fields' => [
                    'privacy_policy' => ['type' => 'markdown', 'label' => 'Privacy Policy', 'rules' => ['nullable', 'string', 'max:60000']],
                    'terms' => ['type' => 'markdown', 'label' => 'Terms & Conditions', 'rules' => ['nullable', 'string', 'max:60000']],
                    'donation_terms' => ['type' => 'markdown', 'label' => 'Donation Terms', 'rules' => ['nullable', 'string', 'max:60000']],
                    'refund_policy' => ['type' => 'markdown', 'label' => 'Refund Policy', 'rules' => ['nullable', 'string', 'max:60000']],
                    'volunteer_terms' => ['type' => 'markdown', 'label' => 'Volunteer Terms', 'rules' => ['nullable', 'string', 'max:60000']],
                ],
            ],

            'integrations' => [
                'label' => 'Integrations',
                'description' => 'Public identifiers only. Secret keys are read from the server .env file and are never stored or displayed here.',
                'source' => self::SOURCE_SETTINGS,
                'fields' => [
                    'ga_measurement_id' => ['type' => 'text', 'label' => 'Google Analytics Measurement ID', 'help' => 'e.g. G-XXXXXXXXXX. Leave blank to disable analytics.', 'rules' => ['nullable', 'string', 'regex:/^G-[A-Z0-9]{4,12}$/i']],
                    'gtm_container_id' => ['type' => 'text', 'label' => 'Google Tag Manager Container ID', 'help' => 'e.g. GTM-XXXXXXX', 'rules' => ['nullable', 'string', 'regex:/^GTM-[A-Z0-9]{4,10}$/i']],
                    'recaptcha_site_key' => ['type' => 'text', 'label' => 'reCAPTCHA Site Key (public)', 'help' => 'The public site key only. The secret key must be set as RECAPTCHA_SECRET_KEY in .env.', 'rules' => ['nullable', 'string', 'max:100', 'regex:/^[\w\-]+$/']],
                ],
            ],

            'seo' => [
                'label' => 'SEO',
                'description' => 'Global search defaults. Individual pages and content inherit these unless they set their own values.',
                'source' => self::SOURCE_SETTINGS,
                'fields' => [
                    'title_suffix' => ['type' => 'text', 'label' => 'Title Suffix', 'help' => 'Appended to page titles, e.g. "Blog — Gauri Ganesh Seva Sanstha". Leave blank to use the website name.', 'rules' => ['nullable', 'string', 'max:60']],
                    'title_separator' => ['type' => 'select', 'label' => 'Title Separator', 'options' => ['—' => '— (em dash)', '|' => '| (pipe)', '·' => '· (dot)', '-' => '- (hyphen)'], 'rules' => ['required'], 'default' => '—'],
                    'default_robots' => ['type' => 'select', 'label' => 'Default Robots Directive', 'help' => 'Applied to public pages that do not set their own. Draft and private pages are never indexable regardless.', 'options' => ['index, follow' => 'index, follow', 'noindex, follow' => 'noindex, follow', 'index, nofollow' => 'index, nofollow', 'noindex, nofollow' => 'noindex, nofollow'], 'rules' => ['required'], 'default' => 'index, follow'],
                    'twitter_site' => ['type' => 'text', 'label' => 'X / Twitter Handle', 'help' => 'e.g. @ggss — used for the twitter:site card attribution.', 'rules' => ['nullable', 'string', 'max:20', 'regex:/^@?[A-Za-z0-9_]{1,15}$/']],
                    'google_site_verification' => ['type' => 'text', 'label' => 'Google Search Console Verification', 'help' => 'The content value of the google-site-verification meta tag.', 'rules' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_\-]+$/']],
                    'bing_site_verification' => ['type' => 'text', 'label' => 'Bing Webmaster Verification', 'help' => 'The content value of the msvalidate.01 meta tag.', 'rules' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_\-]+$/']],
                    'sitemap_enabled' => ['type' => 'boolean', 'label' => 'Publish XML Sitemap', 'help' => 'Serves /sitemap.xml and references it from robots.txt.', 'default' => true],
                    'discourage_indexing' => ['type' => 'boolean', 'label' => 'Discourage Search Engines', 'help' => 'For staging sites only: every page becomes noindex, robots.txt disallows everything and the sitemap is disabled.', 'default' => false],
                ],
            ],

            'maintenance' => [
                'label' => 'Maintenance',
                'description' => 'Take the public website offline while keeping the admin panel available to signed-in administrators.',
                'source' => self::SOURCE_SETTINGS,
                'fields' => [
                    'enabled' => ['type' => 'boolean', 'label' => 'Maintenance Mode', 'help' => 'When on, visitors see the maintenance page. Administrators can still sign in and use the admin panel.', 'default' => false],
                    'heading' => ['type' => 'text', 'label' => 'Heading', 'rules' => ['nullable', 'string', 'max:120'], 'default' => 'We’ll be back shortly'],
                    'message' => ['type' => 'textarea', 'label' => 'Message', 'rules' => ['nullable', 'string', 'max:1000'], 'default' => 'Our website is undergoing scheduled maintenance. Thank you for your patience.'],
                    'expected_back_at' => ['type' => 'datetime', 'label' => 'Expected Back', 'help' => 'Optional — shown to visitors.', 'rules' => ['nullable', 'date']],
                    'allowed_ips' => ['type' => 'textarea', 'label' => 'Allowed IP Addresses', 'help' => 'One per line. These visitors bypass maintenance mode.', 'rules' => ['nullable', 'string', 'max:1000']],
                ],
            ],
        ];
    }
}
