<?php

namespace Tests\Feature\Admin;

use App\Enums\Role as RoleEnum;
use App\Models\OrgProfile;
use App\Models\Setting;
use App\Models\User;
use App\Services\SettingsService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('public');
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::Admin->value);

        return $admin;
    }

    /**
     * @return array<string, mixed>
     */
    private function generalPayload(array $overrides = []): array
    {
        return array_merge([
            'site_name' => 'Seva Test Site',
            'site_tagline' => 'Serving together',
            'site_description' => 'A test description.',
            'default_language' => 'en',
            'default_country' => 'India',
            'timezone' => 'Asia/Kolkata',
            'currency' => 'INR',
            'date_format' => 'd M Y',
        ], $overrides);
    }

    public function test_settings_helper_returns_registry_defaults_before_anything_is_saved(): void
    {
        $this->assertSame('Gauri Ganesh Seva Sanstha', setting('general.site_name'));
        $this->assertSame('INR', setting('donation.currency'));
        $this->assertFalse((bool) setting('maintenance.enabled', false));
        $this->assertNull(setting('social.facebook_url'));
        $this->assertSame('fallback', setting('branding.primary_color', 'fallback'));
    }

    public function test_user_without_manage_settings_permission_is_forbidden(): void
    {
        $editor = User::factory()->create();
        $editor->assignRole(RoleEnum::ContentManager->value);

        $this->actingAs($editor)->get(route('admin.settings.edit', 'general'))->assertForbidden();
        $this->actingAs($editor)->put(route('admin.settings.update', 'general'), $this->generalPayload())->assertForbidden();
        $this->actingAs($editor)->get(route('admin.menu-items.index'))->assertForbidden();

        $this->assertSame('Gauri Ganesh Seva Sanstha', setting('general.site_name'));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.settings.edit', 'general'))->assertRedirect(route('login'));
    }

    public function test_every_settings_tab_renders_for_an_admin(): void
    {
        $admin = $this->admin();

        foreach (['general', 'organization', 'contact', 'social', 'branding', 'donation', 'footer', 'navigation', 'legal', 'integrations', 'maintenance'] as $group) {
            $this->actingAs($admin)->get(route('admin.settings.edit', $group))->assertOk();
        }

        $this->actingAs($admin)->get(route('admin.settings.edit', 'nope'))->assertNotFound();
        $this->actingAs($admin)->get(route('admin.settings.index'))->assertRedirect(route('admin.settings.edit', 'general'));
    }

    public function test_admin_can_update_general_settings_and_they_apply_immediately(): void
    {
        $response = $this->actingAs($this->admin())->put(route('admin.settings.update', 'general'), $this->generalPayload());

        $response->assertRedirect(route('admin.settings.edit', 'general'))->assertSessionHas('status', 'General settings saved.');

        $this->assertDatabaseHas('settings', ['group' => 'general', 'key' => 'site_name', 'value' => 'Seva Test Site']);
        $this->assertSame('Seva Test Site', setting('general.site_name'));
        // Runtime config follows the database (used by mail subjects and 120+ templates).
        $this->assertSame('Seva Test Site', config('app.name'));
        $this->assertSame('Seva Test Site', config('mail.from.name'));

        // Header and footer render the new name; the old one is gone.
        $this->get(route('home'))->assertOk()->assertSee('Seva Test Site')->assertSee('og:site_name" content="Seva Test Site', false);
    }

    public function test_validation_rejects_invalid_general_values(): void
    {
        $response = $this->actingAs($this->admin())->put(route('admin.settings.update', 'general'), $this->generalPayload([
            'site_name' => '',
            'timezone' => 'Mars/Phobos',
            'default_language' => 'xx',
            'currency' => 'BTC',
        ]));

        $response->assertSessionHasErrors(['site_name', 'timezone', 'default_language', 'currency']);
        $this->assertSame('Gauri Ganesh Seva Sanstha', setting('general.site_name'));
    }

    public function test_cache_is_invalidated_on_save_and_reads_come_from_cache(): void
    {
        setting('general.site_name'); // warm
        $this->assertTrue(Cache::has(SettingsService::CACHE_KEY));

        $this->actingAs($this->admin())->put(route('admin.settings.update', 'general'), $this->generalPayload(['site_name' => 'Fresh Name']));

        $this->assertSame('Fresh Name', app(SettingsService::class)->get('general.site_name'));
        $this->assertSame('Fresh Name', Cache::get(SettingsService::CACHE_KEY)['general']['site_name']);

        // A stale cache entry is never trusted over a write.
        Cache::forever(SettingsService::CACHE_KEY, ['general' => ['site_name' => 'Stale']]);
        app(SettingsService::class)->set('general.site_name', 'Newest');
        $this->assertSame('Newest', app(SettingsService::class)->get('general.site_name'));
    }

    public function test_organization_and_contact_tabs_write_to_the_org_profile(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.settings.update', 'organization'), [
            'legal_name' => 'Gauri Ganesh Seva Sanstha Trust',
            'short_name' => 'GGSS',
            'pan_no' => 'ABCDE1234F',
            'section_80g_no' => '',
            'country' => 'India',
        ])->assertSessionHasNoErrors();

        $this->actingAs($admin)->put(route('admin.settings.update', 'contact'), [
            'phone_primary' => '+91 98765 43210',
            'email_primary' => 'hello@example.org',
            'office_hours' => 'Mon–Sat 10–6',
        ])->assertSessionHasNoErrors();

        $profile = OrgProfile::firstOrFail();
        $this->assertSame('GGSS', $profile->short_name);
        $this->assertSame('hello@example.org', $profile->email_primary);
        $this->assertNull($profile->section_80g_no);

        // Exposed through the same helper and visible in the footer.
        $this->assertSame('hello@example.org', setting('contact.email_primary'));
        $this->assertSame('India', setting('organization.country'));
        $this->get(route('home'))->assertOk()->assertSee('hello@example.org')->assertSee('+91 98765 43210')->assertDontSee('80G');
    }

    public function test_organization_validation_rejects_a_malformed_pan(): void
    {
        $this->actingAs($this->admin())->put(route('admin.settings.update', 'organization'), ['pan_no' => '1234'])
            ->assertSessionHasErrors('pan_no');
    }

    public function test_social_urls_are_validated_and_empty_links_are_not_rendered(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.settings.update', 'social'), [
            'facebook_url' => 'javascript:alert(1)',
            'instagram_url' => 'ftp://example.org',
            'youtube_url' => 'not a url',
        ])->assertSessionHasErrors(['facebook_url', 'instagram_url', 'youtube_url']);

        $this->actingAs($admin)->put(route('admin.settings.update', 'social'), [
            'facebook_url' => 'https://facebook.com/ggss',
            'telegram_url' => 'https://t.me/ggss',
            'instagram_url' => '',
        ])->assertSessionHasNoErrors();

        $home = $this->get(route('home'));
        $home->assertOk();
        $home->assertSee('href="https://facebook.com/ggss" target="_blank" rel="noopener noreferrer"', false);
        $home->assertSee('aria-label="Telegram (opens in a new tab)"', false);
        $home->assertDontSee('aria-label="Instagram', false);
        $home->assertDontSee('aria-label="YouTube', false);
    }

    public function test_logo_and_favicon_upload_replace_and_removal(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.settings.update', 'branding'), [
            'logo' => UploadedFile::fake()->image('logo.png', 400, 100),
            'favicon' => UploadedFile::fake()->image('favicon.png', 64, 64),
            'primary_color' => '#123456',
        ])->assertSessionHasNoErrors();

        $logo = Setting::query()->where('group', 'branding')->where('key', 'logo')->firstOrFail();
        $this->assertNotNull($logo->getFirstMedia('image'));
        $this->assertNotNull(setting_media('branding.logo'));
        $this->assertNotNull(setting_media('branding.favicon'));

        $home = $this->get(route('home'));
        $home->assertOk();
        $home->assertSee('<link rel="icon" href="'.setting_media('branding.favicon').'">', false);
        $home->assertSee('--color-primary-700:#123456', false);
        $home->assertSee('alt="Gauri Ganesh Seva Sanstha" class="h-10', false);

        // Replace keeps exactly one file.
        $this->actingAs($admin)->put(route('admin.settings.update', 'branding'), [
            'logo' => UploadedFile::fake()->image('logo2.png', 400, 100),
        ]);
        $this->assertCount(1, $logo->fresh()->getMedia('image'));
        $this->assertSame('logo2.png', $logo->fresh()->getFirstMedia('image')->file_name);

        // Remove.
        $this->actingAs($admin)->put(route('admin.settings.update', 'branding'), ['remove_logo' => 1]);
        $this->assertNull(setting_media('branding.logo'));
        $this->get(route('home'))->assertOk()->assertDontSee('class="h-10 w-auto"', false);
    }

    public function test_branding_uploads_reject_svg_and_bad_colours(): void
    {
        $this->actingAs($this->admin())->put(route('admin.settings.update', 'branding'), [
            'logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
            'favicon' => UploadedFile::fake()->create('fav.txt', 10, 'text/plain'),
            'primary_color' => 'red',
        ])->assertSessionHasErrors(['logo', 'favicon', 'primary_color']);
    }

    public function test_donation_settings_feed_the_donation_config_but_never_secrets(): void
    {
        $this->actingAs($this->admin())->put(route('admin.settings.update', 'donation'), [
            'upi_id' => 'ggss@upi',
            'bank_account_name' => 'Gauri Ganesh Seva Sanstha',
            'bank_name' => 'State Bank',
            'account_number' => '123456789012',
            'ifsc' => 'SBIN0001234',
            'branch' => 'Hadapsar',
            'min_amount' => 50,
            'currency' => 'INR',
        ])->assertSessionHasNoErrors();

        $this->assertSame('ggss@upi', config('donations.gateways.upi.vpa'));
        $this->assertSame('SBIN0001234', config('donations.gateways.bank_transfer.ifsc'));
        $this->assertSame(50.0, config('donations.min_amount'));

        // No gateway secret is ever declared as a setting or stored.
        $this->assertSame(0, Setting::query()->where('key', 'like', '%secret%')->count());
        $this->assertNull(setting('donation.razorpay_secret'));

        $this->actingAs($this->admin())->put(route('admin.settings.update', 'donation'), [
            'upi_id' => 'not-a-vpa', 'ifsc' => 'BAD', 'account_number' => '12ab', 'min_amount' => 0, 'currency' => 'INR',
        ])->assertSessionHasErrors(['upi_id', 'ifsc', 'account_number', 'min_amount']);
    }

    public function test_integrations_tab_shows_env_secret_presence_without_values(): void
    {
        config(['services.recaptcha.secret_key' => 'SUPER-SECRET-VALUE', 'donations.gateways.razorpay.key' => 'rzp_key', 'donations.gateways.razorpay.secret' => 'RZP-SECRET-VALUE']);

        $response = $this->actingAs($this->admin())->get(route('admin.settings.edit', 'integrations'));

        $response->assertOk()->assertSee('Configured')->assertSee('Not set');
        $response->assertDontSee('SUPER-SECRET-VALUE')->assertDontSee('RZP-SECRET-VALUE');

        // And the public site never sees them either.
        $this->get(route('home'))->assertDontSee('SUPER-SECRET-VALUE')->assertDontSee('RZP-SECRET-VALUE');

        $this->actingAs($this->admin())->put(route('admin.settings.update', 'integrations'), [
            'ga_measurement_id' => 'G-ABC123XYZ',
            'gtm_container_id' => '<script>',
        ])->assertSessionHasErrors('gtm_container_id');
    }

    public function test_analytics_snippet_only_renders_when_configured(): void
    {
        $this->get(route('home'))->assertDontSee('googletagmanager.com');

        app(SettingsService::class)->set('integrations.ga_measurement_id', 'G-ABC123XYZ');

        $this->get(route('home'))->assertSee("gtag('config','G-ABC123XYZ')", false);
    }

    public function test_legal_pages_publish_only_when_content_exists(): void
    {
        $this->get(route('legal.show.privacy-policy'))->assertNotFound();
        $this->get(route('home'))->assertDontSee('>Privacy Policy<', false);

        $this->actingAs($this->admin())->put(route('admin.settings.update', 'legal'), [
            'privacy_policy' => "## Your data\n\nWe collect only what we need. <script>alert(1)</script>",
        ])->assertSessionHasNoErrors();

        $page = $this->get(route('legal.show.privacy-policy'));
        $page->assertOk()->assertSee('<h2>Your data</h2>', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->get(route('home'))->assertSee('>Privacy Policy<', false)->assertDontSee('>Terms &amp; Conditions<', false);
        $this->get(route('legal.show.terms'))->assertNotFound();
        $this->get('/legal/unknown')->assertNotFound();
        // The long alias permanently redirects to the canonical short URL.
        $this->get('/legal/privacy-policy')->assertStatus(301)->assertRedirect(route('legal.show.privacy-policy'));
    }

    public function test_footer_uses_footer_settings_with_contact_fallbacks(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.settings.update', 'contact'), ['email_primary' => 'fallback@example.org']);
        $this->get(route('home'))->assertSee('fallback@example.org');

        $this->actingAs($admin)->put(route('admin.settings.update', 'footer'), [
            'description' => 'Footer blurb here.',
            'copyright_text' => '© {year} {name} — all rights reserved',
            'email' => 'footer@example.org',
            'cta_heading' => 'Join the mission',
            'cta_button_label' => 'Donate',
            'cta_button_url' => '/donate',
        ])->assertSessionHasNoErrors();

        $home = $this->get(route('home'));
        // The organisation schema still lists the contact email; the footer link must not.
        $home->assertSee('Footer blurb here.')->assertSee('mailto:footer@example.org', false)->assertDontSee('mailto:fallback@example.org', false);
        $home->assertSee('© '.now()->year.' Gauri Ganesh Seva Sanstha — all rights reserved');
        $home->assertSee('Join the mission');

        $this->actingAs($admin)->put(route('admin.settings.update', 'footer'), ['cta_button_url' => 'javascript:alert(1)'])
            ->assertSessionHasErrors('cta_button_url');
    }
}
