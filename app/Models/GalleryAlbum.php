<?php

namespace App\Models;

use App\Enums\AlbumStatus;
use App\Traits\HasSeo;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class GalleryAlbum extends Model implements HasMedia
{
    use HasSeo, HasSlug, InteractsWithMedia, SoftDeletes;

    protected string $slugSource = 'title';

    protected $fillable = [
        'gallery_category_id',
        'title',
        'slug',
        'description',
        'event_date',
        'location',
        'status',
        'is_featured',
        'order_column',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'is_featured' => 'boolean',
            'status' => AlbumStatus::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(GalleryCategory::class, 'gallery_category_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(GalleryPhoto::class)->orderBy('order_column')->orderBy('id');
    }

    public function activePhotos(): HasMany
    {
        return $this->photos()->where('is_active', true);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(GalleryVideo::class)->orderBy('order_column')->orderBy('id');
    }

    public function activeVideos(): HasMany
    {
        return $this->videos()->where('is_active', true);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', AlbumStatus::Published->value);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order_column')->orderBy('event_date', 'desc');
    }

    /**
     * Cover falls back to the first active photo so albums render even
     * before a dedicated cover image is uploaded.
     */
    public function coverMedia(): ?Media
    {
        return $this->getFirstMedia('cover_image')
            ?? $this->activePhotos()->first()?->getFirstMedia('image');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover_image')->singleFile();
        $this->addMediaCollection('og_image')->singleFile();
    }

    /**
     * Conversions run synchronously (nonQueued) since album covers are
     * low-volume CMS uploads — same trade-off as the other modules.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('webp')
            ->format('webp')
            ->quality(82)
            ->nonQueued();

        $this->addMediaConversion('thumb')
            ->format('webp')
            ->quality(80)
            ->width(640)
            ->nonQueued();
    }
}
