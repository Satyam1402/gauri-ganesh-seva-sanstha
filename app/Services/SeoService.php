<?php

namespace App\Services;

use App\Models\Page;
use App\Models\SeoMeta;
use App\Support\Seo\SeoData;
use App\Support\Seo\StructuredData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Resolves the metadata for a public page and persists admin-entered SEO
 * for any model using the HasSeo trait.
 *
 * Fallback chain (first non-empty wins):
 *   admin-entered seo_meta row for the page/content
 *     → values derived from the content itself (title, excerpt, image)
 *       → global SEO defaults from Site Settings
 *         → safe application defaults
 *
 * The result is a SeoData object rendered once by seo-head.blade.php, so
 * no Blade file assembles metadata on its own.
 */
class SeoService
{
    public const TITLE_MAX = 60;

    public const DESCRIPTION_MAX = 160;

    public function __construct(private SettingsService $settings) {}

    /**
     * Metadata for a page described inline (listings, forms, static
     * pages without a Page row). Keys: title, description, canonical,
     * robots, image, ogType, breadcrumbs, schemas, noindex, keywords,
     * publishedTime, modifiedTime, plain (skip the title suffix).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function make(array $attributes): SeoData
    {
        return $this->resolve(null, $attributes);
    }

    /**
     * Metadata for a model with a HasSeo relation (Page, BlogPost, ...).
     * $fallbacks are derived from the content and override only the
     * global defaults.
     *
     * @param  array<string, mixed>  $fallbacks
     */
    public function forModel(?Model $model, array $fallbacks): SeoData
    {
        $seo = $model?->relationLoaded('seo') ? $model->seo : $model?->seo()->with(['ogImage', 'twitterImage'])->first();

        return $this->resolve($seo, $fallbacks);
    }

    /**
     * Metadata for one of the static Page anchors (home, about, faq, ...),
     * read through the same forever-cache the page controllers use.
     *
     * @param  array<string, mixed>  $fallbacks
     */
    public function forPage(string $slug, array $fallbacks): SeoData
    {
        return $this->forModel($this->pageModel($slug), $fallbacks);
    }

    /**
     * The static Page anchor for a slug, forever-cached (SeoService::sync
     * busts it), or null when none has been seeded.
     */
    public function pageModel(string $slug): ?Page
    {
        return Cache::rememberForever("pages.{$slug}", fn () => Page::query()
            ->where('slug', $slug)
            ->with(['seo.ogImage', 'seo.twitterImage', 'media'])
            ->first()
        );
    }

    /**
     * Metadata for a paginated/filterable listing. The canonical URL keeps
     * only $canonicalParams (e.g. a category filter) plus the page number,
     * so sort/search variants never index as duplicates; on-site search
     * results are noindex.
     *
     * @param  array<string, mixed>  $attributes
     * @param  list<string>  $canonicalParams
     */
    public function listing(Request $request, string $baseUrl, array $attributes, array $canonicalParams = [], ?Model $model = null): SeoData
    {
        $keep = [];

        foreach ($canonicalParams as $param) {
            if ($request->filled($param)) {
                $keep[$param] = $request->query($param);
            }
        }

        $page = $request->integer('page', 1);

        if ($page > 1) {
            $keep['page'] = $page;
        }

        $attributes['canonical'] = $keep ? $baseUrl.'?'.http_build_query($keep) : $baseUrl;

        if ($request->filled('q')) {
            $attributes['noindex'] = true;
        }

        return $model ? $this->forModel($model, $attributes) : $this->make($attributes);
    }

    /**
     * Site-wide defaults, used when a view is rendered without any
     * page-level metadata (error pages, unexpected routes).
     */
    public function defaults(): SeoData
    {
        return $this->resolve(null, ['canonical' => url()->current()]);
    }

    /**
     * Persist admin-entered SEO fields (and OG / Twitter images) for any
     * HasSeo model. Shared by the Page SEO screen and every content
     * module's form, so the write path exists exactly once.
     *
     * @param  array<string, mixed>  $data
     */
    public function sync(Model $model, array $data): SeoMeta
    {
        $seo = $model->seo()->firstOrNew();

        $seo->fill([
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'meta_keywords' => $data['meta_keywords'] ?? null,
            'canonical_url' => $data['canonical_url'] ?? null,
            'robots' => array_key_exists($data['robots'] ?? '', SeoMeta::ROBOTS_OPTIONS) ? $data['robots'] : null,
            'og_title' => $data['og_title'] ?? null,
            'og_description' => $data['og_description'] ?? null,
            'twitter_card' => $data['twitter_card'] ?? 'summary_large_image',
            'twitter_title' => $data['twitter_title'] ?? null,
            'twitter_description' => $data['twitter_description'] ?? null,
            'schema_type' => $data['schema_type'] ?? $seo->schema_type,
        ]);

        foreach (['og_image' => 'og_image_media_id', 'twitter_image' => 'twitter_image_media_id'] as $input => $column) {
            if (($data[$input] ?? null) instanceof UploadedFile) {
                $media = $model->addMedia($data[$input])->toMediaCollection($input);
                $seo->{$column} = $media->id;
            } elseif (! empty($data['remove_'.$input])) {
                $model->clearMediaCollection($input);
                $seo->{$column} = null;
            }
        }

        $model->seo()->save($seo);

        if ($model instanceof Page) {
            Cache::forget("pages.{$model->slug}");
        }

        $this->forgetSitemap();

        return $seo;
    }

    /**
     * Backwards-compatible alias used by PageSeoController.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateSeo(Page $page, array $data): Page
    {
        $this->sync($page, $data);

        return $page->refresh();
    }

    public function forgetSitemap(): void
    {
        Cache::forget(SitemapService::CACHE_KEY);
    }

    /**
     * Global defaults from Site Settings, with safe application fallbacks.
     *
     * @return array{site_name: string, suffix: string, description: string, robots: string, image: ?string, twitter_site: ?string, discourage: bool}
     */
    public function globals(): array
    {
        $siteName = $this->settings->get('general.site_name', config('app.name'));

        return [
            'site_name' => $siteName,
            'suffix' => $this->settings->get('seo.title_suffix') ?? $siteName,
            'separator' => $this->settings->get('seo.title_separator', '—'),
            'description' => $this->settings->get('general.site_description') ?? $this->settings->get('general.site_tagline') ?? $siteName,
            'robots' => $this->settings->get('seo.default_robots', 'index, follow'),
            'image' => $this->settings->mediaUrl('branding.og_image', 'webp') ?? $this->settings->mediaUrl('branding.logo', 'webp'),
            'twitter_site' => $this->settings->get('seo.twitter_site'),
            'discourage' => (bool) $this->settings->get('seo.discourage_indexing', false),
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function resolve(?SeoMeta $seo, array $attributes): SeoData
    {
        $globals = $this->globals();

        // Admin-entered titles are used verbatim; content titles get the
        // site suffix unless the page opts out with 'plain'.
        $baseTitle = $seo?->meta_title ?: ($attributes['title'] ?? null);

        if ($seo?->meta_title) {
            $title = $seo->meta_title;
        } elseif ($baseTitle) {
            $title = ($attributes['plain'] ?? false) ? $baseTitle : $this->suffix($baseTitle, $globals);
        } else {
            $title = $globals['site_name'];
        }

        $description = $this->trim($seo?->meta_description ?: ($attributes['description'] ?? null) ?: $globals['description'], self::DESCRIPTION_MAX);

        $canonical = $seo?->canonical_url ?: ($attributes['canonical'] ?? url()->current());

        $robots = $globals['discourage']
            ? 'noindex, nofollow'
            : ($seo?->robots ?: (($attributes['noindex'] ?? false) ? 'noindex, follow' : ($attributes['robots'] ?? $globals['robots'])));

        $contentImage = $attributes['image'] ?? null;
        $ogImage = $seo?->ogImage?->getUrl() ?? $contentImage ?? $globals['image'];
        $twitterImage = $seo?->twitterImage?->getUrl() ?? $ogImage;

        $ogTitle = $seo?->og_title ?: $baseTitle ?: $globals['site_name'];
        $ogDescription = $this->trim($seo?->og_description ?: $description, 200);

        $data = new SeoData(
            title: $title,
            description: $description,
            canonical: $canonical,
            robots: $robots,
            keywords: $seo?->meta_keywords ?: ($attributes['keywords'] ?? null),
            ogTitle: $ogTitle,
            ogDescription: $ogDescription,
            ogImage: $ogImage,
            ogType: $attributes['ogType'] ?? 'website',
            twitterCard: $seo?->twitter_card ?: ($ogImage ? 'summary_large_image' : 'summary'),
            twitterTitle: $seo?->twitter_title ?: $ogTitle,
            twitterDescription: $this->trim($seo?->twitter_description ?: $ogDescription, 200),
            twitterImage: $twitterImage,
            breadcrumbs: $attributes['breadcrumbs'] ?? [],
            schemas: [],
            publishedTime: $attributes['publishedTime'] ?? null,
            modifiedTime: $attributes['modifiedTime'] ?? null,
        );

        // Breadcrumbs always produce BreadcrumbList; page schemas follow.
        if ($crumbs = StructuredData::breadcrumbs($data->breadcrumbs, $canonical)) {
            $data->withSchema($crumbs);
        }

        foreach ($attributes['schemas'] ?? [] as $schema) {
            if ($schema) {
                $data->withSchema($schema);
            }
        }

        return $data;
    }

    /**
     * "Page Title — Site Name", trimmed so the suffix never pushes the
     * title far past what result pages display.
     *
     * @param  array<string, mixed>  $globals
     */
    private function suffix(string $title, array $globals): string
    {
        $suffix = ' '.$globals['separator'].' '.$globals['suffix'];

        if (Str::endsWith($title, $globals['suffix'])) {
            return $title;
        }

        return $title.$suffix;
    }

    private function trim(?string $text, int $max): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $text)));

        return Str::limit($text, $max, '…');
    }
}
