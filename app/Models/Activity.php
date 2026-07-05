<?php

namespace App\Models;

use App\Enums\ActivityStatus;
use App\Traits\HasSeo;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Activity extends Model implements HasMedia
{
    use HasSeo, HasSlug, InteractsWithMedia, SoftDeletes;

    protected string $slugSource = 'title';

    protected $fillable = [
        'activity_category_id',
        'title',
        'slug',
        'short_description',
        'full_description',
        'activity_date',
        'location',
        'organizer',
        'status',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'activity_date' => 'date',
            'is_featured' => 'boolean',
            'status' => ActivityStatus::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ActivityCategory::class, 'activity_category_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ActivityStatus::Published->value);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured_image')->singleFile();
        $this->addMediaCollection('gallery');
        $this->addMediaCollection('og_image')->singleFile();
    }

    /**
     * Conversions run synchronously (nonQueued) since activity images are
     * low-volume CMS uploads — waiting for a queue worker would make the
     * WebP variant unavailable immediately after upload for no real benefit.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('webp')
            ->format('webp')
            ->nonQueued();
    }
}
