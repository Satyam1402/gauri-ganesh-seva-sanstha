<?php

namespace App\Services;

use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class GalleryPhotoService
{
    public function __construct(private GalleryAlbumService $albumService) {}

    /**
     * Bulk upload: every file becomes a photo row appended to the end of
     * the album, with shared defaults (photographer, caption).
     *
     * @param  list<UploadedFile>  $files
     * @param  array<string, mixed>  $defaults
     * @return list<GalleryPhoto>
     */
    public function uploadPhotos(GalleryAlbum $album, array $files, array $defaults = [], ?int $uploadedBy = null): array
    {
        $nextOrder = ((int) $album->photos()->max('order_column')) + 1;
        $photos = [];

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $photo = $album->photos()->create([
                'caption' => $defaults['caption'] ?? null,
                'alt_text' => $defaults['alt_text'] ?? null,
                'photographer' => $defaults['photographer'] ?? null,
                'uploaded_by' => $uploadedBy,
                'is_active' => true,
                'order_column' => $nextOrder++,
            ]);

            $photo->addMedia($file)->toMediaCollection('image');

            $photos[] = $photo;
        }

        $this->albumService->forgetCache();

        return $photos;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updatePhoto(GalleryPhoto $photo, array $data): GalleryPhoto
    {
        $photo->update([
            'caption' => $data['caption'] ?? null,
            'alt_text' => $data['alt_text'] ?? null,
            'photographer' => $data['photographer'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? $photo->is_active),
        ]);

        $this->albumService->forgetCache();

        return $photo->refresh();
    }

    public function toggleActive(GalleryPhoto $photo): GalleryPhoto
    {
        $photo->update(['is_active' => ! $photo->is_active]);
        $this->albumService->forgetCache();

        return $photo;
    }

    public function deletePhoto(GalleryPhoto $photo): bool
    {
        // Media files are removed with the model by the media library.
        $deleted = (bool) $photo->delete();
        $this->albumService->forgetCache();

        return $deleted;
    }

    /**
     * Delete a batch of photos, scoped to one album so a stray id can never
     * remove photos from another album. Returns the number deleted.
     *
     * @param  list<int>  $ids
     */
    public function bulkDelete(GalleryAlbum $album, array $ids): int
    {
        $count = 0;

        foreach ($album->photos()->whereIn('id', $ids)->get() as $photo) {
            // Deleted one-by-one (not a mass delete) so the media library
            // fires its cleanup for each photo's files.
            $photo->delete();
            $count++;
        }

        $this->albumService->forgetCache();

        return $count;
    }

    /**
     * @param  list<int>  $orderedIds
     */
    public function reorder(GalleryAlbum $album, array $orderedIds): void
    {
        DB::transaction(function () use ($album, $orderedIds): void {
            foreach (array_values($orderedIds) as $position => $id) {
                $album->photos()->whereKey($id)->update(['order_column' => $position + 1]);
            }
        });

        $this->albumService->forgetCache();
    }
}
