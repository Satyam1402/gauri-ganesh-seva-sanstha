<?php

namespace App\Traits;

use App\Models\SeoMeta;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasSeo
{
    public function seo(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoMetable', 'seo_metable_type', 'seo_metable_id');
    }

    public function seoTitle(?string $fallback = null): ?string
    {
        return $this->seo?->meta_title ?: $fallback;
    }

    public function seoDescription(?string $fallback = null): ?string
    {
        return $this->seo?->meta_description ?: $fallback;
    }
}
