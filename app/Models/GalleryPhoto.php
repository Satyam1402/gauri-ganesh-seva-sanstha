<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class GalleryPhoto extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'gallery_album_id',
        'caption',
        'alt_text',
        'photographer',
        'uploaded_by',
        'is_active',
        'order_column',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(GalleryAlbum::class, 'gallery_album_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Alt text falls back to the caption, then the album title, so images
     * are never rendered without a usable alt attribute.
     */
    public function resolvedAltText(): string
    {
        return $this->alt_text ?: ($this->caption ?: ($this->album?->title ?? 'Gallery photo'));
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }

    /**
     * thumb feeds the grid, webp the lightbox — both compressed WebP so the
     * originals are only served as a download/fallback. Photos are uploaded
     * in batches from the admin, so conversions run synchronously to be
     * visible immediately after upload.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('webp')
            ->format('webp')
            ->quality(82)
            ->width(1920)
            ->nonQueued();

        $this->addMediaConversion('thumb')
            ->format('webp')
            ->quality(80)
            ->width(640)
            ->nonQueued();
    }
}
