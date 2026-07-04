<?php

namespace App\Services;

use App\Models\Page;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;

class SeoService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function updateSeo(Page $page, array $data): Page
    {
        $seo = $page->seo()->firstOrNew();

        $seo->fill([
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'meta_keywords' => $data['meta_keywords'] ?? null,
            'canonical_url' => $data['canonical_url'] ?? null,
            'og_title' => $data['og_title'] ?? null,
            'og_description' => $data['og_description'] ?? null,
            'twitter_card' => $data['twitter_card'] ?? 'summary_large_image',
            'schema_type' => $data['schema_type'] ?? null,
        ]);

        if (($data['og_image'] ?? null) instanceof UploadedFile) {
            $media = $page->addMedia($data['og_image'])->toMediaCollection('og_image');
            $seo->og_image_media_id = $media->id;
        } elseif (! empty($data['remove_og_image'])) {
            $page->clearMediaCollection('og_image');
            $seo->og_image_media_id = null;
        }

        $page->seo()->save($seo);

        Cache::forget("pages.{$page->slug}");

        return $page->refresh();
    }
}
