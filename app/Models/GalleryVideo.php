<?php

namespace App\Models;

use App\Enums\VideoProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class GalleryVideo extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'gallery_album_id',
        'title',
        'provider',
        'video_url',
        'video_id',
        'description',
        'is_active',
        'order_column',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'provider' => VideoProvider::class,
        ];
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(GalleryAlbum::class, 'gallery_album_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Privacy-friendly embed URL for iframe providers; the file URL for
     * self-hosted videos (rendered in a <video> tag instead).
     */
    public function embedUrl(): ?string
    {
        return match ($this->provider) {
            VideoProvider::Youtube => $this->video_id ? "https://www.youtube-nocookie.com/embed/{$this->video_id}" : null,
            VideoProvider::Vimeo => $this->video_id ? "https://player.vimeo.com/video/{$this->video_id}" : null,
            VideoProvider::SelfHosted => $this->getFirstMediaUrl('video_file') ?: null,
        };
    }

    /**
     * Uploaded thumbnail wins; YouTube videos fall back to the automatic
     * thumbnail YouTube hosts for every video.
     */
    public function thumbnailUrl(): ?string
    {
        $media = $this->getFirstMedia('thumbnail');

        if ($media) {
            return $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : $media->getUrl();
        }

        if ($this->provider === VideoProvider::Youtube && $this->video_id) {
            return "https://img.youtube.com/vi/{$this->video_id}/hqdefault.jpg";
        }

        return null;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('thumbnail')->singleFile();

        $this->addMediaCollection('video_file')
            ->singleFile()
            ->acceptsMimeTypes(['video/mp4', 'video/webm', 'video/ogg']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // Thumbnails only — video files are never converted.
        if ($media?->collection_name !== 'thumbnail') {
            return;
        }

        $this->addMediaConversion('thumb')
            ->format('webp')
            ->quality(80)
            ->width(640)
            ->nonQueued();
    }
}
