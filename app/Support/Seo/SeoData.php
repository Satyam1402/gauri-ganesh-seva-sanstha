<?php

namespace App\Support\Seo;

/**
 * Fully-resolved metadata for one public page. Built by SeoService (which
 * applies the page → content → global → application fallback chain) and
 * rendered once by partials/frontend/seo-head.blade.php. Views never
 * assemble metadata themselves.
 */
final class SeoData
{
    /**
     * @param  list<array{label: string, url?: string|null}>  $breadcrumbs
     * @param  list<array<string, mixed>>  $schemas  JSON-LD graphs to emit.
     */
    public function __construct(
        public string $title,
        public string $description,
        public string $canonical,
        public string $robots = 'index, follow',
        public ?string $keywords = null,
        public ?string $ogTitle = null,
        public ?string $ogDescription = null,
        public ?string $ogImage = null,
        public string $ogType = 'website',
        public string $twitterCard = 'summary_large_image',
        public ?string $twitterTitle = null,
        public ?string $twitterDescription = null,
        public ?string $twitterImage = null,
        public array $breadcrumbs = [],
        public array $schemas = [],
        public ?string $publishedTime = null,
        public ?string $modifiedTime = null,
    ) {}

    public function isIndexable(): bool
    {
        return ! str_contains($this->robots, 'noindex');
    }

    /**
     * Breadcrumbs in the shape x-ui.breadcrumbs expects.
     *
     * @return list<array{label: string, url: string|null}>
     */
    public function breadcrumbItems(): array
    {
        return array_map(fn (array $crumb) => ['label' => $crumb['label'], 'url' => $crumb['url'] ?? null], $this->breadcrumbs);
    }

    public function withSchema(array $schema): self
    {
        $this->schemas[] = $schema;

        return $this;
    }
}
