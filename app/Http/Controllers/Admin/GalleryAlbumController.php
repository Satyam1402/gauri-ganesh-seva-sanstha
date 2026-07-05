<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AlbumStatus;
use App\Enums\VideoProvider;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGalleryAlbumRequest;
use App\Http\Requests\Admin\UpdateGalleryAlbumRequest;
use App\Interfaces\GalleryAlbumRepositoryInterface;
use App\Interfaces\GalleryCategoryRepositoryInterface;
use App\Models\GalleryAlbum;
use App\Services\GalleryAlbumService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GalleryAlbumController extends Controller
{
    public function __construct(
        private GalleryAlbumRepositoryInterface $albums,
        private GalleryCategoryRepositoryInterface $categories,
        private GalleryAlbumService $albumService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', GalleryAlbum::class);

        $filters = $request->only(['q', 'category', 'status', 'featured', 'sort', 'direction', 'trashed']);

        return view('admin.gallery-albums.index', [
            'albums' => $this->albums->adminSearch($filters, 15),
            'categories' => $this->categories->allOrdered(),
            'statuses' => AlbumStatus::options(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', GalleryAlbum::class);

        return view('admin.gallery-albums.create', [
            'categories' => $this->categories->allOrdered(),
            'statuses' => AlbumStatus::options(),
        ]);
    }

    public function store(StoreGalleryAlbumRequest $request): RedirectResponse
    {
        $this->authorize('create', GalleryAlbum::class);

        $album = $this->albumService->createAlbum($request->validated());

        return redirect()->route('admin.gallery-albums.edit', $album)
            ->with('status', 'Album created — now add photos below.');
    }

    public function edit(GalleryAlbum $galleryAlbum): View
    {
        $this->authorize('update', $galleryAlbum);

        return view('admin.gallery-albums.edit', [
            'album' => $galleryAlbum->load(['category', 'media', 'seo', 'photos.media', 'videos.media']),
            'categories' => $this->categories->allOrdered(),
            'statuses' => AlbumStatus::options(),
            'providers' => VideoProvider::options(),
        ]);
    }

    public function update(UpdateGalleryAlbumRequest $request, GalleryAlbum $galleryAlbum): RedirectResponse
    {
        $this->authorize('update', $galleryAlbum);

        $this->albumService->updateAlbum($galleryAlbum, $request->validated());

        return redirect()->route('admin.gallery-albums.edit', $galleryAlbum)
            ->with('status', 'Album updated successfully.');
    }

    public function destroy(GalleryAlbum $galleryAlbum): RedirectResponse
    {
        $this->authorize('delete', $galleryAlbum);

        $this->albumService->deleteAlbum($galleryAlbum);

        return redirect()->route('admin.gallery-albums.index')
            ->with('status', 'Album moved to trash.');
    }

    public function restore(GalleryAlbum $galleryAlbum): RedirectResponse
    {
        $this->authorize('restore', $galleryAlbum);

        $this->albumService->restoreAlbum($galleryAlbum);

        return redirect()->route('admin.gallery-albums.index', ['trashed' => 1])
            ->with('status', 'Album restored successfully.');
    }

    public function toggleFeatured(GalleryAlbum $galleryAlbum): RedirectResponse
    {
        $this->authorize('update', $galleryAlbum);

        $this->albumService->toggleFeatured($galleryAlbum);

        return back()->with('status', $galleryAlbum->is_featured ? 'Album marked as featured.' : 'Album removed from featured.');
    }

    public function publish(GalleryAlbum $galleryAlbum): RedirectResponse
    {
        $this->authorize('update', $galleryAlbum);

        $this->albumService->publish($galleryAlbum);

        return back()->with('status', 'Album published.');
    }

    public function unpublish(GalleryAlbum $galleryAlbum): RedirectResponse
    {
        $this->authorize('update', $galleryAlbum);

        $this->albumService->unpublish($galleryAlbum);

        return back()->with('status', 'Album unpublished.');
    }
}
