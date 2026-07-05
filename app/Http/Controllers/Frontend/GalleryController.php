<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\AlbumStatus;
use App\Http\Controllers\Controller;
use App\Interfaces\GalleryAlbumRepositoryInterface;
use App\Interfaces\GalleryCategoryRepositoryInterface;
use App\Models\GalleryAlbum;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GalleryController extends Controller
{
    public function __construct(
        private GalleryAlbumRepositoryInterface $albums,
        private GalleryCategoryRepositoryInterface $categories,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'category', 'sort']);

        return view('frontend.gallery.index', [
            'albums' => $this->albums->publishedPaginated($filters, 12),
            'categories' => $this->categories->activeOrdered(),
            'featured' => $this->albums->featuredList(3),
            'latest' => $this->albums->latest(3),
            'filters' => $filters,
        ]);
    }

    public function show(GalleryAlbum $album): View
    {
        abort_unless($album->status === AlbumStatus::Published, 404);

        return view('frontend.gallery.show', [
            'album' => $album->load(['category', 'media', 'seo.ogImage']),
            'photos' => $album->activePhotos()->with('media')->get(),
            'videos' => $album->activeVideos()->with('media')->get(),
            'related' => $this->albums->related($album, 3),
        ]);
    }
}
