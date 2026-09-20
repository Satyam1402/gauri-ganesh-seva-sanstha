<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SeoMeta extends Model
{
    /**
     * Allowed robots directives (null on the row means "inherit").
     *
     * @var array<string, string>
     */
    public const ROBOTS_OPTIONS = [
        'index, follow' => 'Index, follow (default for public content)',
        'noindex, follow' => 'No index, follow links',
        'index, nofollow' => 'Index, do not follow links',
        'noindex, nofollow' => 'No index, no follow (hidden from search)',
    ];

    protected $table = 'seo_meta';

    protected $fillable = [
        'meta_title',
        'meta_description',
        'meta_keywords',
        'canonical_url',
        'robots',
        'og_title',
        'og_description',
        'og_image_media_id',
        'twitter_card',
        'twitter_title',
        'twitter_description',
        'twitter_image_media_id',
        'schema_type',
        'structured_data',
    ];

    protected function casts(): array
    {
        return [
            'structured_data' => 'array',
        ];
    }

    public function seoMetable(): MorphTo
    {
        return $this->morphTo();
    }

    public function ogImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'og_image_media_id');
    }

    public function twitterImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'twitter_image_media_id');
    }

    /**
     * True when the row carries no admin-entered values at all — used by
     * the SEO manager to flag content that relies entirely on fallbacks.
     */
    public function isEmpty(): bool
    {
        return blank($this->meta_title) && blank($this->meta_description) && $this->og_image_media_id === null;
    }
}
