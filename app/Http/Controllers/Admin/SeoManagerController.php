<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\DonationCampaign;
use App\Models\Event;
use App\Models\GalleryAlbum;
use App\Models\Page;
use App\Models\SlugRedirect;
use App\Services\SeoService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * One screen to see the whole site's SEO state: global defaults, every
 * static page, per-content coverage, crawler endpoints and redirects.
 */
class SeoManagerController extends Controller
{
    /**
     * Static Page anchors and how they map to public URLs / schema.
     *
     * @var array<string, array{route: string, schema: string}>
     */
    public const PAGES = [
        'home' => ['route' => 'home', 'schema' => 'NGO + WebSite'],
        'about' => ['route' => 'about', 'schema' => 'AboutPage + NGO + BreadcrumbList'],
        'donate' => ['route' => 'donations.donate', 'schema' => 'BreadcrumbList'],
        'volunteer' => ['route' => 'volunteer.create', 'schema' => 'BreadcrumbList'],
        'contact' => ['route' => 'contact', 'schema' => 'ContactPage + NGO + BreadcrumbList'],
        'testimonials' => ['route' => 'testimonials.index', 'schema' => 'CollectionPage + BreadcrumbList'],
        'faq' => ['route' => 'faq.index', 'schema' => 'FAQPage + BreadcrumbList'],
        'partners' => ['route' => 'partners.index', 'schema' => 'CollectionPage + BreadcrumbList'],
    ];

    public function __construct(
        private SeoService $seoService,
        private SettingsService $settings,
    ) {}

    public function index(): View
    {
        Gate::authorize('manage settings');

        $pages = Page::query()->with('seo')->get()->keyBy('slug');

        $staticPages = collect(self::PAGES)->map(function (array $meta, string $slug) use ($pages) {
            $page = $pages[$slug] ?? null;

            return [
                'slug' => $slug,
                'title' => $page?->title ?? ucfirst($slug),
                'url' => route($meta['route']),
                'schema' => $meta['schema'],
                'page' => $page,
                'status' => $page === null ? 'missing' : ($page->seo === null || $page->seo->isEmpty() ? 'defaults' : 'custom'),
                'robots' => $page?->seo?->robots,
            ];
        });

        $contentTypes = collect([
            ['label' => 'Activities', 'model' => Activity::class, 'route' => 'admin.activities.index', 'scope' => fn ($q) => $q->published()],
            ['label' => 'Activity Categories', 'model' => ActivityCategory::class, 'route' => 'admin.activity-categories.index', 'scope' => fn ($q) => $q->where('is_active', true)],
            ['label' => 'Blog Posts', 'model' => BlogPost::class, 'route' => 'admin.blog-posts.index', 'scope' => fn ($q) => $q->published()],
            ['label' => 'Blog Categories', 'model' => BlogCategory::class, 'route' => 'admin.blog-categories.index', 'scope' => fn ($q) => $q->where('is_active', true)],
            ['label' => 'Events', 'model' => Event::class, 'route' => 'admin.events.index', 'scope' => fn ($q) => $q->public()],
            ['label' => 'Gallery Albums', 'model' => GalleryAlbum::class, 'route' => 'admin.gallery-albums.index', 'scope' => fn ($q) => $q->published()],
            ['label' => 'Donation Campaigns', 'model' => DonationCampaign::class, 'route' => 'admin.donation-campaigns.index', 'scope' => fn ($q) => $q->whereIn('status', ['active', 'completed'])],
        ])->map(function (array $type) {
            $query = $type['scope']($type['model']::query());
            $total = (clone $query)->count();
            $withMeta = (clone $query)->whereHas('seo', fn ($q) => $q->whereNotNull('meta_description'))->count();
            $noindex = (clone $query)->whereHas('seo', fn ($q) => $q->where('robots', 'like', '%noindex%'))->count();

            return [
                'label' => $type['label'],
                'route' => $type['route'],
                'total' => $total,
                'with_meta' => $withMeta,
                'noindex' => $noindex,
            ];
        });

        return view('admin.seo.index', [
            'globals' => $this->seoService->globals(),
            'staticPages' => $staticPages,
            'contentTypes' => $contentTypes,
            'redirects' => SlugRedirect::query()->latest()->limit(20)->get(),
            'redirectCount' => SlugRedirect::query()->count(),
            'sitemapEnabled' => (bool) $this->settings->get('seo.sitemap_enabled', true),
            'discourage' => (bool) $this->settings->get('seo.discourage_indexing', false),
            'verification' => [
                'Google' => filled($this->settings->get('seo.google_site_verification')),
                'Bing' => filled($this->settings->get('seo.bing_site_verification')),
            ],
        ]);
    }

    /**
     * Drop the cached sitemap so the next crawl sees fresh content now.
     */
    public function refreshSitemap(): RedirectResponse
    {
        Gate::authorize('manage settings');

        $this->seoService->forgetSitemap();

        return back()->with('status', 'Sitemap cache cleared — it will be rebuilt on the next request.');
    }
}
