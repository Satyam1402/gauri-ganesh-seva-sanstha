<?php

namespace Tests\Feature;

use App\Models\Partner;
use App\Models\PartnerType;
use App\Services\PartnerService;
use Database\Seeders\HomeSectionsSeeder;
use Database\Seeders\PagesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PartnersPageTest extends TestCase
{
    use RefreshDatabase;

    private function type(array $overrides = []): PartnerType
    {
        return PartnerType::create(array_merge(['name' => 'Sponsor', 'slug' => 'sponsor', 'is_active' => true], $overrides));
    }

    private function partner(array $overrides = []): Partner
    {
        return Partner::create(array_merge([
            'name' => 'Sunrise CSR Trust',
            'short_description' => 'Funds our annual school-kit drive.',
            'website_url' => 'https://sunrise.example.org',
            'status' => 'active',
            'admin_notes' => 'PRIVATE-NOTE-MARKER',
            'email' => 'private@example.org',
            'phone' => '+91 90000 00000',
        ], $overrides));
    }

    public function test_partners_page_lists_only_active_partners(): void
    {
        $this->seed(PagesSeeder::class);

        $this->partner(['name' => 'Active Org']);
        $this->partner(['name' => 'Draft Org', 'status' => 'draft']);
        $this->partner(['name' => 'Inactive Org', 'status' => 'inactive']);
        $this->partner(['name' => 'Archived Org', 'status' => 'archived']);
        $this->partner(['name' => 'Trashed Org'])->delete();

        $response = $this->get(route('partners.index'));

        $response->assertOk();
        $response->assertSee('Active Org');
        $response->assertDontSee('Draft Org');
        $response->assertDontSee('Inactive Org');
        $response->assertDontSee('Archived Org');
        $response->assertDontSee('Trashed Org');
    }

    public function test_inactive_and_draft_partners_return_404_on_their_detail_page(): void
    {
        $inactive = $this->partner(['name' => 'Inactive Org', 'status' => 'inactive']);
        $draft = $this->partner(['name' => 'Draft Org', 'status' => 'draft']);
        $active = $this->partner(['name' => 'Active Org']);

        $this->get(route('partners.show', $inactive))->assertNotFound();
        $this->get(route('partners.show', $draft))->assertNotFound();
        $this->get(route('partners.show', $active))->assertOk()->assertSee('Active Org');
    }

    public function test_partners_in_a_hidden_type_are_not_shown(): void
    {
        $hidden = $this->type(['name' => 'Old', 'slug' => 'old', 'is_active' => false]);
        $byType = $this->partner(['name' => 'Hidden By Type', 'partner_type_id' => $hidden->id]);
        $this->partner(['name' => 'Visible Untyped']);

        $this->get(route('partners.index'))->assertOk()->assertSee('Visible Untyped')->assertDontSee('Hidden By Type');
        $this->get(route('partners.show', $byType))->assertNotFound();
    }

    public function test_private_fields_are_never_rendered_publicly(): void
    {
        $partner = $this->partner(['is_featured' => true]);

        foreach ([route('partners.index'), route('partners.show', $partner), route('home')] as $url) {
            $this->get($url)->assertOk()
                ->assertDontSee('PRIVATE-NOTE-MARKER')
                ->assertDontSee('private@example.org')
                ->assertDontSee('90000 00000');
        }

        $array = $partner->toArray();
        $this->assertArrayNotHasKey('admin_notes', $array);
        $this->assertArrayNotHasKey('email', $array);
        $this->assertArrayNotHasKey('phone', $array);
    }

    public function test_type_filter_narrows_and_unknown_types_fall_back(): void
    {
        $sponsor = $this->type();
        $ngo = $this->type(['name' => 'NGO Partner', 'slug' => 'ngo-partner']);
        $this->partner(['name' => 'Sponsor Org', 'partner_type_id' => $sponsor->id]);
        $this->partner(['name' => 'NGO Org', 'partner_type_id' => $ngo->id]);

        $response = $this->get(route('partners.index', ['type' => 'ngo-partner']));
        $response->assertOk();
        $this->assertSame(['NGO Org'], $response->viewData('partners')->pluck('name')->all());
        $this->assertTrue($response->viewData('currentType')->is($ngo));

        $response = $this->get(route('partners.index', ['type' => 'nope']));
        $response->assertOk();
        $this->assertNull($response->viewData('currentType'));
        $this->assertCount(2, $response->viewData('partners'));
    }

    public function test_featured_partners_appear_in_the_featured_strip_and_on_the_homepage(): void
    {
        $this->seed(HomeSectionsSeeder::class);

        $this->partner(['name' => 'Featured Org', 'is_featured' => true]);
        $this->partner(['name' => 'Regular Org', 'is_featured' => false]);

        $response = $this->get(route('partners.index'));
        $response->assertOk()->assertSee('Featured Partners');
        $this->assertSame(['Featured Org'], $response->viewData('featured')->pluck('name')->all());

        $this->get(route('home'))->assertOk()->assertSee('Featured Org')->assertDontSee('Regular Org');
    }

    public function test_display_order_controls_public_ordering(): void
    {
        $this->partner(['name' => 'Second', 'display_order' => 2]);
        $this->partner(['name' => 'First', 'display_order' => 1]);
        $this->partner(['name' => 'Third', 'display_order' => 3]);

        $response = $this->get(route('partners.index'));

        $this->assertSame(['First', 'Second', 'Third'], $response->viewData('partners')->pluck('name')->all());
    }

    public function test_logo_renders_with_alt_text_and_website_opens_safely(): void
    {
        Storage::fake('public');

        $partner = $this->partner(['logo_alt' => 'Sunrise wordmark']);
        $partner->addMedia(UploadedFile::fake()->image('logo.png', 400, 200))->toMediaCollection('logo');

        $response = $this->get(route('partners.show', $partner));

        $response->assertOk();
        $response->assertSee('alt="Sunrise wordmark"', false);
        $response->assertSee('loading="lazy"', false);
        $response->assertSee('href="https://sunrise.example.org" target="_blank" rel="noopener noreferrer"', false);
        $response->assertSee('(opens in a new tab)');
    }

    public function test_partner_without_logo_shows_its_name_as_text(): void
    {
        $partner = $this->partner(['name' => 'No Logo Org', 'website_url' => null]);

        $this->get(route('partners.index'))->assertOk()->assertSee('No Logo Org');
        $this->get(route('partners.show', $partner))->assertOk()->assertDontSee('<img', false);
    }

    public function test_page_renders_seo_meta_and_breadcrumbs_only(): void
    {
        $this->seed(PagesSeeder::class);
        $this->partner();

        $response = $this->get(route('partners.index'));

        $response->assertOk();
        $response->assertSee('<title>Partners &amp; Sponsors — Gauri Ganesh Seva Sanstha</title>', false);
        $response->assertSee('<link rel="canonical" href="'.route('partners.index').'">', false);
        $response->assertSee('property="og:title"', false);
        $response->assertSee('name="twitter:card"', false);
        $response->assertSee('"@type":"BreadcrumbList"', false);
        $response->assertDontSee('"@type":"Organization"', false);
    }

    public function test_section_component_uses_type_then_falls_back(): void
    {
        $sponsor = $this->type();
        $this->partner(['name' => 'Sponsor Org', 'partner_type_id' => $sponsor->id]);
        $this->partner(['name' => 'Featured Generic', 'is_featured' => true]);

        // Donate page pulls the "sponsor" type.
        $this->get(route('donations.donate'))
            ->assertOk()
            ->assertSee('Our Sponsors')
            ->assertSee('Sponsor Org')
            ->assertDontSee('Featured Generic');

        // Activities page asks for featured → falls back are not needed here.
        $this->get(route('activities.index'))
            ->assertOk()
            ->assertSee('Partners on the Ground')
            ->assertSee('Featured Generic');
    }

    public function test_section_is_hidden_when_there_are_no_partners(): void
    {
        $this->get(route('activities.index'))->assertOk()->assertDontSee('Partners on the Ground');
    }

    public function test_public_cache_is_invalidated_when_a_partner_changes(): void
    {
        $partner = $this->partner(['name' => 'Original Org']);

        $this->get(route('partners.index'))->assertSee('Original Org');

        app(PartnerService::class)->deactivate($partner);

        $this->get(route('partners.index'))->assertDontSee('Original Org');
    }
}
