<?php

namespace App\Http\Requests\Concerns;

use App\Models\SeoMeta;
use Illuminate\Validation\Rule;

/**
 * Validation for the shared admin SEO fields (admin/partials/seo-fields).
 * Spread into any content form request: ...$this->seoRules().
 */
trait HasSeoRules
{
    /**
     * @return array<string, mixed>
     */
    protected function seoRules(): array
    {
        return [
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],
            'canonical_url' => ['nullable', 'string', 'max:255', 'url:http,https'],
            'robots' => ['nullable', 'string', Rule::in(array_keys(SeoMeta::ROBOTS_OPTIONS))],
            'og_title' => ['nullable', 'string', 'max:70'],
            'og_description' => ['nullable', 'string', 'max:200'],
            'og_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=200,min_height=200'],
            'remove_og_image' => ['nullable', 'boolean'],
            'twitter_card' => ['nullable', 'string', 'in:summary,summary_large_image'],
            'twitter_title' => ['nullable', 'string', 'max:70'],
            'twitter_description' => ['nullable', 'string', 'max:200'],
            'twitter_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=200,min_height=200'],
            'remove_twitter_image' => ['nullable', 'boolean'],
            'schema_type' => ['nullable', 'string', 'max:50'],
        ];
    }
}
