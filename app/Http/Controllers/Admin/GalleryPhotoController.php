<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkDeleteGalleryPhotosRequest;
use App\Http\Requests\Admin\ReorderGalleryPhotosRequest;
use App\Http\Requests\Admin\UpdateGalleryPhotoRequest;
use App\Http\Requests\Admin\UploadGalleryPhotosRequest;
use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;
use App\Services\GalleryPhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GalleryPhotoController extends Controller
{
    public function __construct(private GalleryPhotoService $photoService) {}

    /**
     * Bulk upload — also serves the drag & drop dropzone, which posts the
     * same multipart form via fetch and expects JSON back.
     */
    public function store(UploadGalleryPhotosRequest $request, GalleryAlbum $galleryAlbum): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $galleryAlbum);

        $photos = $this->photoService->uploadPhotos(
            $galleryAlbum,
            $request->file('photos', []),
            $request->only(['caption', 'alt_text', 'photographer']),
            $request->user()->id,
        );

        $message = count($photos).' '.str('photo')->plural(count($photos)).' uploaded.';

        if ($request->expectsJson()) {
            return response()->json(['status' => 'ok', 'uploaded' => count($photos), 'message' => $message]);
        }

        return redirect()->route('admin.gallery-albums.edit', $galleryAlbum)
            ->with('status', $message);
    }

    public function update(UpdateGalleryPhotoRequest $request, GalleryAlbum $galleryAlbum, GalleryPhoto $photo): RedirectResponse
    {
        $this->authorize('update', $galleryAlbum);
        abort_unless($photo->gallery_album_id === $galleryAlbum->id, 404);

        $this->photoService->updatePhoto($photo, $request->validated());

        return back()->with('status', 'Photo details saved.');
    }

    public function toggle(Request $request, GalleryAlbum $galleryAlbum, GalleryPhoto $photo): RedirectResponse
    {
        $this->authorize('update', $galleryAlbum);
        abort_unless($photo->gallery_album_id === $galleryAlbum->id, 404);

        $this->photoService->toggleActive($photo);

        return back()->with('status', $photo->is_active ? 'Photo is now visible.' : 'Photo hidden from the public gallery.');
    }

    public function destroy(GalleryAlbum $galleryAlbum, GalleryPhoto $photo): RedirectResponse
    {
        $this->authorize('update', $galleryAlbum);
        abort_unless($photo->gallery_album_id === $galleryAlbum->id, 404);

        $this->photoService->deletePhoto($photo);

        return back()->with('status', 'Photo deleted.');
    }

    public function bulkDestroy(BulkDeleteGalleryPhotosRequest $request, GalleryAlbum $galleryAlbum): RedirectResponse
    {
        $this->authorize('update', $galleryAlbum);

        $count = $this->photoService->bulkDelete($galleryAlbum, $request->validated('ids'));

        return back()->with('status', "{$count} photos deleted.");
    }

    public function reorder(ReorderGalleryPhotosRequest $request, GalleryAlbum $galleryAlbum): JsonResponse
    {
        $this->authorize('update', $galleryAlbum);

        $this->photoService->reorder($galleryAlbum, $request->validated('order'));

        return response()->json(['status' => 'ok']);
    }
}
