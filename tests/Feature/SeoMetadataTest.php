<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Http\Middleware\RedirectTrailingSlash;
use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\DonationCampaign;
use App\Models\Event;
use App\Models\Faq;
use App\Models\GalleryAlbum;
use App\Models\GalleryCategory;
use App\Models\Page;
use App\Models\User;
use App\Services\SettingsService;
use Database\Seeders\HomeSectionsSeeder;
use Database\Seeders\PagesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SeoMetadataTest extends TestCase
{
    use RefreshDatabase;

    private function blogPost(array $overrides = []): BlogPost
    {
        $category = BlogCategory::firstOrCreate(['slug' => 'news'], ['name' => 'News', 'is_active' => true]);
        $author = User::factory()->create(['name' => 'Asha Editor']);

        return BlogPost::create(array_merge([
            'blog_category_id' => $category->id,
            'user_id' => $author->id,
            'title' => 'Winter Blanket Drive Reaches 1,200 Families',
            'excerpt' => 'How volunteers distributed blankets across three neighbourhoods in one weekend.',
            'content' => 'Full story body.',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ], $overrides));
    }

    private function event(array $overrides = []): Event
    {
        return Event::create(array_merge([
            'title' => 'Free Health Camp',
            'short_description' => 'Free checkups for everyone.',
            'full_description' => 'Details.',
            'start_date' => now()->addDays(10)->toDateString(),
            'start_time' => '10:00',
            'venue' => 'Community Hall',
            'city' => 'Pune',
            'status' => 'published',
        ], $overrides));
    }

    private function activity(array $overrides = []): Activity
    {
        $category = ActivityCategory::firstOrCreate(['slug' => 'food-distribution'], ['name' => 'Food Distribution', 'is_active' => true]);

        return Activity::create(array_merge([
            'activity_category_id' => $category->id,
            'title' => 'Weekly Community Meal Drive',
            'short_description' => 'Hot meals for 300 families every Sunday.',
            'full_description' => 'Details.',
            'activity_date' => now()->subDays(3)->toDateString(),
            'status' => 'published',
        ], $overrides));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function jsonLd(TestResponse $response): array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $response->getContent(), $matches);

        return array_map(fn (string $json) => json_decode($json, true), $matches[1]);
    }

    public function test_homepage_renders_full_metadata_and_organization_schema(): void
    {
        $this->seed([PagesSeeder::class, HomeSectionsSeeder::class]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('<title>Gauri Ganesh Seva Sanstha — NGO for Food, Education &amp; Health</title>', false);
        $response->assertSee('<link rel="canonical" href="'.url('/').'">', false);
        $response->assertSee('<meta name="robots" content="index, follow">', false);
        $response->assertSee('<meta property="og:type" content="website">', false);
        $response->assertSee('<meta property="og:site_name" content="Gauri Ganesh Seva Sanstha">', false);
        $response->assertSee('<meta name="twitter:card" content="', false);

        $types = array_column($this->jsonLd($response), '@type');
        $this->assertContains('NGO', $types);
        $this->assertContains('WebSite', $types);
        $this->assertNotContains('BreadcrumbList', $types, 'the homepage has no breadcrumb trail');

        // Only one of each meta tag — no duplicates from stray @sections.
        $this->assertSame(1, substr_count($response->getContent(), '<link rel="canonical"'));
        $this->assertSame(1, substr_count($response->getContent(), 'name="description"'));
        $this->assertSame(1, substr_count($response->getContent(), '<title>'));
    }

    public function test_organization_schema_only_contains_verified_settings(): void
    {
        $response = $this->get(route('home'));
        $org = collect($this->jsonLd($response))->firstWhere('@type', 'NGO');

        $this->assertSame('Gauri Ganesh Seva Sanstha', $org['name']);
        $this->assertArrayNotHasKey('telephone', $org);
        $this->assertArrayNotHasKey('address', $org);
        $this->assertArrayNotHasKey('sameAs', $org);

        app(SettingsService::class)->updateGroup('contact', ['phone_primary' => '+91 98765 43210']);
        app(SettingsService::class)->updateGroup('social', ['facebook_url' => 'https://facebook.com/ggss']);

        $org = collect($this->jsonLd($this->get(route('home'))))->firstWhere('@type', 'NGO');
        $this->assertSame('+91 98765 43210', $org['telephone']);
        $this->assertSame(['https://facebook.com/ggss'], $org['sameAs']);
    }

    public function test_blog_post_renders_article_metadata_breadcrumbs_and_schema(): void
    {
        Storage::fake('public');
        $post = $this->blogPost();
        $post->addMedia(UploadedFile::fake()->image('cover.jpg', 1200, 630))->toMediaCollection('featured_image');

        $response = $this->get(route('blog.show', $post));

        $response->assertOk();
        $response->assertSee('<title>Winter Blanket Drive Reaches 1,200 Families — Gauri Ganesh Seva Sanstha</title>', false);
        $response->assertSee('<meta name="description" content="How volunteers distributed blankets across three neighbourhoods in one weekend.">', false);
        $response->assertSee('<link rel="canonical" href="'.route('blog.show', $post).'">', false);
        $response->assertSee('<meta property="og:type" content="article">', false);
        $response->assertSee('<meta property="article:published_time"', false);
        $response->assertSee('<meta property="og:image" content="'.$post->getFirstMedia('featured_image')->getUrl().'">', false);
        $response->assertSee('<meta name="twitter:card" content="summary_large_image">', false);
        $response->assertSee('<meta name="twitter:image" content="', false);

        $schemas = collect($this->jsonLd($response));
        $article = $schemas->firstWhere('@type', 'Article');
        $this->assertSame('Winter Blanket Drive Reaches 1,200 Families', $article['headline']);
        $this->assertSame('Asha Editor', $article['author']['name']);
        $this->assertSame(route('blog.show', $post), $article['mainEntityOfPage']['@id']);

        $crumbs = $schemas->firstWhere('@type', 'BreadcrumbList');
        $this->assertSame(['Home', 'Blog', 'News', 'Winter Blanket Drive Reaches 1,200 Families'], array_column($crumbs['itemListElement'], 'name'));
        $this->assertSame(route('blog.category', 'news'), $crumbs['itemListElement'][2]['item']);

        // Visible breadcrumbs match the structured data.
        $response->assertSee('aria-label="Breadcrumb"', false)->assertSee('>News</a>', false);
    }

    public function test_content_seo_row_overrides_derived_metadata(): void
    {
        $post = $this->blogPost();
        $post->seo()->create([
            'meta_title' => 'Custom Post Title',
            'meta_description' => 'Custom description.',
            'canonical_url' => 'https://example.org/original-story',
            'robots' => 'noindex, follow',
            'og_title' => 'Custom Share Title',
            'twitter_card' => 'summary',
            'twitter_title' => 'Custom Tweet',
        ]);

        $response = $this->get(route('blog.show', $post));

        $response->assertSee('<title>Custom Post Title</title>', false);
        $response->assertSee('content="Custom description."', false);
        $response->assertSee('<link rel="canonical" href="https://example.org/original-story">', false);
        $response->assertSee('<meta name="robots" content="noindex, follow">', false);
        $response->assertSee('<meta property="og:title" content="Custom Share Title">', false);
        $response->assertSee('<meta name="twitter:card" content="summary">', false);
        $response->assertSee('<meta name="twitter:title" content="Custom Tweet">', false);
    }

    public function test_event_renders_event_schema_with_accurate_dates_and_status(): void
    {
        $event = $this->event(['status' => 'cancelled']);

        $response = $this->get(route('events.show', $event));

        $response->assertOk();
        $schema = collect($this->jsonLd($response))->firstWhere('@type', 'Event');
        $this->assertSame('Free Health Camp', $schema['name']);
        $this->assertSame('https://schema.org/EventCancelled', $schema['eventStatus']);
        $this->assertStringStartsWith(now()->addDays(10)->format('Y-m-d').'T10:00', $schema['startDate']);
        $this->assertSame('Community Hall', $schema['location']['name']);
        $this->assertSame('Pune', $schema['location']['address']['addressLocality']);
        $this->assertArrayNotHasKey('image', $schema, 'no image is invented when the event has none');
    }

    public function test_activity_and_category_pages_render_metadata_and_canonicals(): void
    {
        $activity = $this->activity();

        $show = $this->get(route('activities.show', $activity));
        $show->assertOk();
        $show->assertSee('<title>Weekly Community Meal Drive — Gauri Ganesh Seva Sanstha</title>', false);
        $show->assertSee('<link rel="canonical" href="'.route('activities.show', $activity).'">', false);
        $this->assertContains('Article', array_column($this->jsonLd($show), '@type'));

        // Category filter is an indexable page; sort/search params never leak into the canonical.
        $category = $this->get(route('activities.index', ['category' => 'food-distribution', 'sort' => 'oldest']));
        $category->assertOk();
        $category->assertSee('<title>Food Distribution Activities — Gauri Ganesh Seva Sanstha</title>', false);
        $category->assertSee('<link rel="canonical" href="'.route('activities.index').'?category=food-distribution">', false);
        $category->assertSee('<meta name="robots" content="index, follow">', false);

        $crumbs = collect($this->jsonLd($category))->firstWhere('@type', 'BreadcrumbList');
        $this->assertSame(['Home', 'Activities', 'Food Distribution'], array_column($crumbs['itemListElement'], 'name'));
    }

    public function test_search_results_are_noindex_and_pagination_canonicalises_to_itself(): void
    {
        $this->blogPost();

        $search = $this->get(route('blog.index', ['q' => 'blanket']));
        $search->assertOk();
        $search->assertSee('<meta name="robots" content="noindex, follow">', false);
        $search->assertSee('<link rel="canonical" href="'.route('blog.index').'">', false);

        $page2 = $this->get(route('blog.index', ['page' => 2, 'sort' => 'oldest']));
        $page2->assertOk();
        $page2->assertSee('<link rel="canonical" href="'.route('blog.index').'?page=2">', false);
        $page2->assertSee('<meta name="robots" content="index, follow">', false);
    }

    public function test_donation_campaign_and_gallery_album_metadata(): void
    {
        $campaign = DonationCampaign::create([
            'name' => 'School Kits 2026',
            'short_description' => 'Kits for 500 children.',
            'full_description' => 'Details.',
            'goal_amount' => 100000,
            'status' => 'active',
        ]);

        $response = $this->get(route('donations.campaigns.show', $campaign));
        $response->assertOk();
        $response->assertSee('<title>School Kits 2026 — Gauri Ganesh Seva Sanstha</title>', false);
        $response->assertSee('<meta name="description" content="Kits for 500 children.">', false);
        $response->assertSee('<link rel="canonical" href="'.route('donations.campaigns.show', $campaign).'">', false);
        $this->assertNotContains('DonateAction', array_column($this->jsonLd($response), '@type'));

        $galleryCategory = GalleryCategory::create(['name' => 'Events', 'is_active' => true]);
        $album = GalleryAlbum::create([
            'gallery_category_id' => $galleryCategory->id,
            'title' => 'Diwali Distribution 2025',
            'description' => 'Sweets and lamps for 200 families.',
            'status' => 'published',
        ]);

        $albumResponse = $this->get(route('gallery.show', $album));
        $albumResponse->assertOk();
        $albumResponse->assertSee('<title>Diwali Distribution 2025 — Gauri Ganesh Seva Sanstha</title>', false);
        $this->assertContains('ImageGallery', array_column($this->jsonLd($albumResponse), '@type'));
    }

    public function test_faq_page_emits_faqpage_only_for_visible_questions(): void
    {
        $this->seed(PagesSeeder::class);
        Faq::create(['question' => 'Is my donation tax-deductible?', 'answer' => 'Only if an 80G certificate is on file.', 'status' => 'published', 'published_at' => now()->subDay()]);
        Faq::create(['question' => 'Hidden draft question?', 'answer' => 'Draft answer.', 'status' => 'draft']);

        $response = $this->get(route('faq.index'));
        $faq = collect($this->jsonLd($response))->firstWhere('@type', 'FAQPage');
        $this->assertCount(1, $faq['mainEntity']);
        $this->assertSame('Is my donation tax-deductible?', $faq['mainEntity'][0]['name']);

        $search = $this->get(route('faq.index', ['q' => 'donation']));
        $this->assertNotContains('FAQPage', array_column($this->jsonLd($search), '@type'));
    }

    public function test_draft_deleted_and_private_pages_are_not_indexable(): void
    {
        $draft = $this->blogPost(['title' => 'Draft Story', 'status' => 'draft']);
        $deleted = $this->blogPost(['title' => 'Deleted Story']);
        $deleted->delete();

        $this->get(route('blog.show', $draft))->assertNotFound();
        $this->get(route('blog.show', $deleted))->assertNotFound();

        $this->get(route('login'))->assertOk()->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_admin_and_transactional_pages_carry_noindex(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole(Role::Admin->value);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);

        auth()->logout();
        $this->get(route('volunteer.create'))->assertOk()->assertSee('<meta name="robots" content="index, follow">', false);
    }

    public function test_trailing_slashes_redirect_permanently(): void
    {
        // The test client normalises URLs itself, so exercise the middleware directly.
        $middleware = new RedirectTrailingSlash;
        $next = fn () => response('passed');

        $response = $middleware->handle(Request::create('http://localhost/about/'), $next);
        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame('http://localhost/about', $response->headers->get('Location'));

        $response = $middleware->handle(Request::create('http://localhost/blog/?page=2'), $next);
        $this->assertSame('http://localhost/blog?page=2', $response->headers->get('Location'));

        $this->assertSame('passed', $middleware->handle(Request::create('http://localhost/'), $next)->getContent());
        $this->assertSame('passed', $middleware->handle(Request::create('http://localhost/about/', 'POST'), $next)->getContent());
    }

    public function test_global_defaults_and_discourage_indexing_apply_everywhere(): void
    {
        $settings = app(SettingsService::class);
        $settings->updateGroup('seo', ['title_suffix' => 'GGSS', 'title_separator' => '|', 'default_robots' => 'index, follow', 'twitter_site' => 'ggss_ngo', 'google_site_verification' => 'abc123']);

        $response = $this->get(route('about'));
        $response->assertSee('<title>About Us | GGSS</title>', false);
        $response->assertSee('<meta name="twitter:site" content="@ggss_ngo">', false);
        $response->assertSee('<meta name="google-site-verification" content="abc123">', false);

        $settings->set('seo.discourage_indexing', true);

        $this->get(route('about'))->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        $this->get('/robots.txt')->assertOk()->assertSee("Disallow: /\n", false)->assertDontSee('Sitemap:');
        $this->get('/sitemap.xml')->assertNotFound();
    }

    public function test_page_seo_editor_is_restricted_to_authorised_admins(): void
    {
        $this->seed([PagesSeeder::class, RolesAndPermissionsSeeder::class]);
        $page = Page::where('slug', 'about')->firstOrFail();

        $viewer = User::factory()->create();
        $viewer->assignRole(Role::Viewer->value);
        $this->actingAs($viewer)->get(route('admin.pages.seo.edit', $page))->assertForbidden();
        $this->actingAs($viewer)->get(route('admin.seo.index'))->assertForbidden();

        auth()->logout();
        $this->get(route('admin.seo.index'))->assertRedirect(route('login'));
    }
}
