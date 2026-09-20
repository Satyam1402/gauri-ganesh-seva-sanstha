<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Event;
use App\Models\SlugRedirect;
use App\Models\User;
use App\Services\SeoService;
use App\Services\SettingsService;
use App\Services\SitemapService;
use Database\Seeders\PagesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SitemapAndSlugTest extends TestCase
{
    use RefreshDatabase;

    private function blogPost(array $overrides = []): BlogPost
    {
        $category = BlogCategory::firstOrCreate(['slug' => 'news'], ['name' => 'News', 'is_active' => true]);

        return BlogPost::create(array_merge([
            'blog_category_id' => $category->id,
            'user_id' => User::factory()->create()->id,
            'title' => 'Published Story',
            'excerpt' => 'Excerpt.',
            'content' => 'Body.',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ], $overrides));
    }

    private function activity(array $overrides = []): Activity
    {
        $category = ActivityCategory::firstOrCreate(['slug' => 'food'], ['name' => 'Food', 'is_active' => true]);

        return Activity::create(array_merge([
            'activity_category_id' => $category->id,
            'title' => 'Meal Drive',
            'short_description' => 'Meals.',
            'full_description' => 'Details.',
            'activity_date' => now()->toDateString(),
            'status' => 'published',
        ], $overrides));
    }

    public function test_sitemap_lists_public_content_and_excludes_everything_else(): void
    {
        $this->seed(PagesSeeder::class);

        $published = $this->blogPost(['title' => 'Published Story']);
        $draft = $this->blogPost(['title' => 'Draft Story', 'status' => 'draft']);
        $scheduled = $this->blogPost(['title' => 'Scheduled Story', 'published_at' => now()->addWeek()]);
        $deleted = $this->blogPost(['title' => 'Deleted Story']);
        $deleted->delete();
        $noindex = $this->blogPost(['title' => 'Hidden Story']);
        $noindex->seo()->create(['robots' => 'noindex, follow']);

        $activity = $this->activity();
        $draftActivity = $this->activity(['title' => 'Draft Drive', 'status' => 'draft']);
        $event = Event::create(['title' => 'Camp', 'short_description' => 'x', 'full_description' => 'x', 'start_date' => now()->addDay()->toDateString(), 'status' => 'published']);

        $response = $this->get('/sitemap.xml');

        $response->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $xml = $response->getContent();

        foreach ([url('/'), route('about'), route('blog.index'), route('activities.index'), route('events.index'), route('donations.donate'), route('faq.index'), route('partners.index'), route('volunteer.create'),
            route('blog.show', $published), route('blog.category', 'news'), route('activities.show', $activity), route('events.show', $event)] as $expected) {
            $this->assertStringContainsString('<loc>'.$expected.'</loc>', $xml, "missing {$expected}");
        }

        foreach ([route('blog.show', $draft), route('blog.show', $scheduled), route('blog.show', $deleted), route('blog.show', $noindex), route('activities.show', $draftActivity), url('/admin'), url('/login'), '?q='] as $excluded) {
            $this->assertStringNotContainsString($excluded, $xml, "should not list {$excluded}");
        }

        // Real last-modified dates, not fabricated ones.
        $this->assertStringContainsString('<lastmod>'.$published->updated_at->toAtomString().'</lastmod>', $xml);
        // Legal pages appear only once they have content.
        $this->assertStringNotContainsString(url('/privacy-policy'), $xml);
        app(SettingsService::class)->set('legal.privacy_policy', 'We keep your data safe.');
        app(SeoService::class)->forgetSitemap();
        $this->assertStringContainsString('<loc>'.url('/privacy-policy').'</loc>', $this->get('/sitemap.xml')->getContent());
    }

    public function test_sitemap_is_cached_and_busted_when_seo_is_saved(): void
    {
        $this->get('/sitemap.xml')->assertOk();
        $this->assertTrue(Cache::has(SitemapService::CACHE_KEY));

        $post = $this->blogPost(['title' => 'Late Story']);
        // Still cached — the new post is not there yet.
        $this->assertStringNotContainsString(route('blog.show', $post), $this->get('/sitemap.xml')->getContent());

        app(SeoService::class)->sync($post, ['meta_title' => 'Late Story']);
        $this->assertFalse(Cache::has(SitemapService::CACHE_KEY));
        $this->assertStringContainsString(route('blog.show', $post), $this->get('/sitemap.xml')->getContent());
    }

    public function test_robots_txt_allows_public_content_blocks_private_paths_and_references_the_sitemap(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $body = $response->getContent();

        $this->assertStringStartsWith("User-agent: *\n", $body);
        foreach (['Disallow: /admin', 'Disallow: /login', 'Disallow: /donation/', 'Disallow: /*?q=', 'Allow: /build/', 'Allow: /storage/', 'Sitemap: '.route('sitemap')] as $line) {
            $this->assertStringContainsString($line, $body);
        }
        $this->assertStringNotContainsString("Disallow: /\n", $body);
        $this->assertStringNotContainsString('Disallow: /blog', $body);
    }

    public function test_slugs_are_lowercase_url_safe_and_unique(): void
    {
        $first = $this->blogPost(['title' => 'Winter Drive 2026!']);
        $second = $this->blogPost(['title' => 'Winter Drive 2026!']);
        $third = $this->blogPost(['title' => 'Custom', 'slug' => 'Winter DRIVE 2026!']);

        $this->assertSame('winter-drive-2026', $first->slug);
        $this->assertSame('winter-drive-2026-2', $second->slug);
        $this->assertSame('winter-drive-2026-3', $third->slug, 'admin-supplied slugs are normalised and de-duplicated');

        // A trashed record still reserves its slug.
        $second->delete();
        $this->assertSame('winter-drive-2026-4', $this->blogPost(['title' => 'Winter Drive 2026'])->slug);
    }

    public function test_changing_a_slug_records_a_permanent_redirect_from_the_old_url(): void
    {
        $post = $this->blogPost(['title' => 'Original Story']);
        $oldUrl = route('blog.show', $post);

        $post->update(['slug' => 'renamed-story']);

        $this->assertDatabaseHas('slug_redirects', ['model_type' => $post->getMorphClass(), 'old_slug' => 'original-story', 'new_slug' => 'renamed-story']);
        $this->get($oldUrl)->assertStatus(301)->assertRedirect(route('blog.show', 'renamed-story'));
        $this->get(route('blog.show', 'renamed-story'))->assertOk();

        // Query strings survive; unrelated 404s stay 404.
        $this->get($oldUrl.'?utm=x')->assertRedirect(route('blog.show', 'renamed-story').'?utm=x');
        $this->get('/blog/never-existed')->assertNotFound();
        $this->get('/activities/original-story')->assertNotFound();
    }

    public function test_slug_redirect_chains_collapse_and_never_loop(): void
    {
        $post = $this->blogPost(['title' => 'Alpha']);
        $post->update(['slug' => 'beta']);
        $post->update(['slug' => 'gamma']);

        $this->assertSame('gamma', SlugRedirect::where('old_slug', 'alpha')->value('new_slug'), 'A→B→C collapses to A→C');
        $this->get('/blog/alpha')->assertRedirect(route('blog.show', 'gamma'));

        // Renaming back to an old slug removes the now-contradictory redirect.
        $post->update(['slug' => 'alpha']);
        $this->assertDatabaseMissing('slug_redirects', ['old_slug' => 'alpha']);
        $this->get('/blog/alpha')->assertOk();
        $this->get('/blog/gamma')->assertRedirect(route('blog.show', 'alpha'));
    }

    public function test_slug_redirects_only_apply_to_get_requests_and_the_matching_model(): void
    {
        $activity = $this->activity(['title' => 'Old Drive']);
        $activity->update(['slug' => 'new-drive']);

        $this->get('/activities/old-drive')->assertRedirect(route('activities.show', 'new-drive'));
        $this->get('/blog/old-drive')->assertNotFound();
        $this->post('/activities/old-drive')->assertStatus(405);
    }
}
