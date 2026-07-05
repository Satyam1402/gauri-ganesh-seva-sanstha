<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGalleryVideoRequest;
use App\Http\Requests\Admin\UpdateGalleryVideoRequest;
use App\Models\GalleryAlbum;
use App\Models\GalleryVideo;
use App\Services\GalleryVideoService;
use Illuminate\Http\RedirectResponse;

class GalleryVideoController extends Controller
{
    public function __construct(private GalleryVideoService $videoService) {}

    public function store(StoreGalleryVideoRequest $request, GalleryAlbum $galleryAlbum): RedirectResponse
    {
        $this->authorize('update', $galleryAlbum);

        $this->videoService->addVideo($galleryAlbum, array_merge(
            $request->validated(),
            [
                'thumbnail' => $request->file('thumbnail'),
                'video_file' => $request->file('video_file'),
            ],
        ));

        return redirect()->route('admin.gallery-albums.edit', $galleryAlbum)
            ->with('status', 'Video added to the album.');
    }

    public function update(UpdateGalleryVideoRequest $request, GalleryAlbum $galleryAlbum, GalleryVideo $video): RedirectResponse
    {
        $this->authorize('update', $galleryAlbum);
        abort_unless($video->gallery_album_id === $galleryAlbum->id, 404);

        $this->videoService->updateVideo($video, array_merge(
            $request->validated(),
            [
                'thumbnail' => $request->file('thumbnail'),
                'video_file' => $request->file('video_file'),
            ],
        ));

        return back()->with('status', 'Video updated.');
    }

    public function destroy(GalleryAlbum $galleryAlbum, GalleryVideo $video): RedirectResponse
    {
        $this->authorize('update', $galleryAlbum);
        abort_unless($video->gallery_album_id === $galleryAlbum->id, 404);

        $this->videoService->deleteVideo($video);

        return back()->with('status', 'Video removed.');
    }
}
