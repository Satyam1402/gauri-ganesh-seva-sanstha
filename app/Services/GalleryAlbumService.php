<?php

namespace App\Services;

use App\Enums\AlbumStatus;
use App\Interfaces\GalleryAlbumRepositoryInterface;
use App\Models\GalleryAlbum;
use App\Repositories\GalleryAlbumRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;

class GalleryAlbumService
{
    public function __construct(private GalleryAlbumRepositoryInterface $albums) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createAlbum(array $data): GalleryAlbum
    {
        $album = $this->albums->create([
            'gallery_category_id' => $data['gallery_category_id'] ?? null,
            'title' => $data['title'],
            'slug' => $data['slug'] ?? null,
            'description' => $data['description'] ?? null,
            'event_date' => $data['event_date'] ?? null,
            'location' => $data['location'] ?? null,
            'status' => $data['status'],
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'order_column' => $data['order_column'] ?? 0,
        ]);

        if ($data['cover_image'] ?? null instanceof UploadedFile) {
            $album->addMedia($data['cover_image'])->toMediaCollection('cover_image');
        }

        $this->syncSeo($album, $data);
        $this->forgetCache();

        return $album->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateAlbum(GalleryAlbum $album, array $data): GalleryAlbum
    {
        $this->albums->update($album, [
            'gallery_category_id' => $data['gallery_category_id'] ?? null,
            'title' => $data['title'],
            'slug' => $data['slug'] ?? $album->slug,
            'description' => $data['description'] ?? null,
            'event_date' => $data['event_date'] ?? null,
            'location' => $data['location'] ?? null,
            'status' => $data['status'],
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'order_column' => $data['order_column'] ?? $album->order_column,
        ]);

        if ($data['cover_image'] ?? null instanceof UploadedFile) {
            $album->addMedia($data['cover_image'])->toMediaCollection('cover_image');
        } elseif (! empty($data['remove_cover_image'])) {
            $album->clearMediaCollection('cover_image');
        }

        $this->syncSeo($album, $data);
        $this->forgetCache();

        return $album->refresh();
    }

    public function deleteAlbum(GalleryAlbum $album): bool
    {
        $deleted = (bool) $album->delete();
        $this->forgetCache();

        return $deleted;
    }

    public function restoreAlbum(GalleryAlbum $album): GalleryAlbum
    {
        $album->restore();
        $this->forgetCache();

        return $album;
    }

    public function toggleFeatured(GalleryAlbum $album): GalleryAlbum
    {
        $this->albums->update($album, ['is_featured' => ! $album->is_featured]);
        $this->forgetCache();

        return $album;
    }

    public function publish(GalleryAlbum $album): GalleryAlbum
    {
        $this->albums->update($album, ['status' => AlbumStatus::Published->value]);
        $this->forgetCache();

        return $album;
    }

    public function unpublish(GalleryAlbum $album): GalleryAlbum
    {
        $this->albums->update($album, ['status' => AlbumStatus::Draft->value]);
        $this->forgetCache();

        return $album;
    }

    /**
     * Public gallery caches (latest/featured) must refresh whenever albums
     * or their photos change.
     */
    public function forgetCache(): void
    {
        Cache::forget(GalleryAlbumRepository::CACHE_PREFIX.'.latest.6');
        Cache::forget(GalleryAlbumRepository::CACHE_PREFIX.'.latest.3');
        Cache::forget(GalleryAlbumRepository::CACHE_PREFIX.'.featured.3');
        Cache::forget(GalleryAlbumRepository::CACHE_PREFIX.'.featured.6');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncSeo(GalleryAlbum $album, array $data): void
    {
        $seo = $album->seo()->firstOrNew();

        $seo->fill([
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'meta_keywords' => $data['meta_keywords'] ?? null,
            'canonical_url' => $data['canonical_url'] ?? null,
            'og_title' => $data['og_title'] ?? null,
            'og_description' => $data['og_description'] ?? null,
            'twitter_card' => $data['twitter_card'] ?? 'summary_large_image',
            'schema_type' => $data['schema_type'] ?? 'ImageGallery',
        ]);

        if ($data['og_image'] ?? null instanceof UploadedFile) {
            $media = $album->addMedia($data['og_image'])->toMediaCollection('og_image');
            $seo->og_image_media_id = $media->id;
        } elseif (! empty($data['remove_og_image'])) {
            $album->clearMediaCollection('og_image');
            $seo->og_image_media_id = null;
        }

        $album->seo()->save($seo);
    }
}
