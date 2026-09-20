<?php

namespace App\Services;

use App\Http\Controllers\Frontend\LegalPageController;
use App\Models\Activity;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\DonationCampaign;
use App\Models\Event;
use App\Models\GalleryAlbum;
use App\Models\Page;
use App\Models\Partner;
use App\Models\SeoMeta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

/**
 * Builds the XML sitemap from live content: only published, non-deleted,
 * indexable URLs, with real last-modified dates. Cached for 30 minutes
 * and busted whenever SEO metadata is saved.
 */
class SitemapService
{
    public const CACHE_KEY = 'seo.sitemap.xml';

    public const CACHE_MINUTES = 30;

    public function __construct(private SettingsService $settings) {}

    public function render(): string
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(self::CACHE_MINUTES), fn () => $this->build()->render());
    }

    public function build(): Sitemap
    {
        $sitemap = Sitemap::create();
        $noindex = $this->noindexOwners();

        // Static pages. lastmod comes from the Page row (SEO edits bump it).
        $pages = Page::query()->get()->keyBy('slug');

        foreach ([
            'home' => [url('/'), 'daily', 1.0],
            'about' => [route('about'), 'monthly', 0.8],
            'volunteer' => [route('volunteer.create'), 'monthly', 0.7],
            'contact' => [route('contact'), 'monthly', 0.6],
            'testimonials' => [route('testimonials.index'), 'weekly', 0.6],
            'faq' => [route('faq.index'), 'weekly', 0.6],
            'partners' => [route('partners.index'), 'monthly', 0.5],
        ] as $slug => [$url, $frequency, $priority]) {
            $page = $pages->get($slug);

            if ($page && in_array(Page::class.':'.$page->id, $noindex, true)) {
                continue;
            }

            $sitemap->add($this->url($url, $page?->updated_at, $frequency, $priority));
        }

        // Listing pages.
        $sitemap->add($this->url(route('activities.index'), Activity::published()->max('updated_at'), 'weekly', 0.8));
        $sitemap->add($this->url(route('events.index'), Event::public()->max('updated_at'), 'weekly', 0.8));
        $sitemap->add($this->url(route('blog.index'), BlogPost::published()->max('updated_at'), 'daily', 0.8));
        $sitemap->add($this->url(route('gallery.index'), GalleryAlbum::published()->max('updated_at'), 'weekly', 0.6));
        $sitemap->add($this->url(route('donations.campaigns.index'), DonationCampaign::query()->whereIn('status', ['active', 'completed'])->max('updated_at'), 'weekly', 0.9));
        $sitemap->add($this->url(route('donations.donate'), null, 'monthly', 0.9));

        // Content.
        Activity::published()->select(['id', 'slug', 'updated_at'])->orderBy('id')->each(function (Activity $activity) use ($sitemap, $noindex) {
            if (! in_array(Activity::class.':'.$activity->id, $noindex, true)) {
                $sitemap->add($this->url(route('activities.show', $activity), $activity->updated_at, 'monthly', 0.6));
            }
        });

        Event::public()->select(['id', 'slug', 'updated_at'])->orderBy('id')->each(function (Event $event) use ($sitemap, $noindex) {
            if (! in_array(Event::class.':'.$event->id, $noindex, true)) {
                $sitemap->add($this->url(route('events.show', $event), $event->updated_at, 'weekly', 0.7));
            }
        });

        BlogPost::published()->select(['id', 'slug', 'updated_at'])->orderBy('id')->each(function (BlogPost $post) use ($sitemap, $noindex) {
            if (! in_array(BlogPost::class.':'.$post->id, $noindex, true)) {
                $sitemap->add($this->url(route('blog.show', $post), $post->updated_at, 'monthly', 0.7));
            }
        });

        BlogCategory::active()->whereHas('posts', fn ($q) => $q->published())->select(['id', 'slug', 'updated_at'])->each(function (BlogCategory $category) use ($sitemap) {
            $sitemap->add($this->url(route('blog.category', $category), $category->updated_at, 'weekly', 0.5));
        });

        GalleryAlbum::published()->select(['id', 'slug', 'updated_at'])->orderBy('id')->each(function (GalleryAlbum $album) use ($sitemap, $noindex) {
            if (! in_array(GalleryAlbum::class.':'.$album->id, $noindex, true)) {
                $sitemap->add($this->url(route('gallery.show', $album), $album->updated_at, 'monthly', 0.5));
            }
        });

        DonationCampaign::query()->whereIn('status', ['active', 'completed'])->select(['id', 'slug', 'updated_at'])->orderBy('id')->each(function (DonationCampaign $campaign) use ($sitemap, $noindex) {
            if (! in_array(DonationCampaign::class.':'.$campaign->id, $noindex, true)) {
                $sitemap->add($this->url(route('donations.campaigns.show', $campaign), $campaign->updated_at, 'weekly', 0.8));
            }
        });

        Partner::active()->select(['id', 'slug', 'updated_at'])->orderBy('id')->each(function (Partner $partner) use ($sitemap) {
            $sitemap->add($this->url(route('partners.show', $partner), $partner->updated_at, 'monthly', 0.4));
        });

        // Legal pages exist only once content has been entered.
        foreach (array_keys(LegalPageController::published($this->settings)) as $slug) {
            $sitemap->add($this->url(route('legal.show.'.$slug), null, 'yearly', 0.3));
        }

        return $sitemap;
    }

    private function url(string $loc, mixed $lastModified, string $frequency, float $priority): Url
    {
        $url = Url::create($loc)->setChangeFrequency($frequency)->setPriority($priority);

        if ($lastModified) {
            $url->setLastModificationDate(Carbon::parse($lastModified));
        }

        return $url;
    }

    /**
     * "Model:id" keys for content whose admin-entered robots directive
     * contains noindex — such pages must not be advertised in the sitemap.
     *
     * @return list<string>
     */
    private function noindexOwners(): array
    {
        return SeoMeta::query()
            ->where('robots', 'like', '%noindex%')
            ->get(['seo_metable_type', 'seo_metable_id'])
            ->map(fn (SeoMeta $row) => $row->seo_metable_type.':'.$row->seo_metable_id)
            ->all();
    }
}
