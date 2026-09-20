<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\AlbumStatus;
use App\Http\Controllers\Controller;
use App\Interfaces\GalleryAlbumRepositoryInterface;
use App\Interfaces\GalleryCategoryRepositoryInterface;
use App\Models\GalleryAlbum;
use App\Support\Seo\StructuredData;
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
            'seo' => $this->seo()->listing($request, route('gallery.index'), [
                'title' => 'Gallery',
                'description' => 'Photos and videos from the drives, camps and programmes run by '.setting('general.site_name').'.',
                'breadcrumbs' => [['label' => 'Home', 'url' => route('home')], ['label' => 'Gallery']],
                'schemas' => [StructuredData::webPage('CollectionPage', 'Gallery', route('gallery.index'))],
            ], ['category']),
        ]);
    }

    public function show(GalleryAlbum $album): View
    {
        abort_unless($album->status === AlbumStatus::Published, 404);

        $album->load(['category', 'media', 'seo.ogImage', 'seo.twitterImage']);
        $photos = $album->activePhotos()->with('media')->get();
        $url = route('gallery.show', $album);
        $cover = $album->getFirstMedia('cover_image') ?? $photos->first()?->getFirstMedia('image');

        return view('frontend.gallery.show', [
            'album' => $album,
            'photos' => $photos,
            'videos' => $album->activeVideos()->with('media')->get(),
            'related' => $this->albums->related($album, 3),
            'seo' => $this->seo()->forModel($album, [
                'title' => $album->title,
                'description' => $album->description,
                'canonical' => $url,
                'image' => $cover?->getUrl(),
                'breadcrumbs' => [['label' => 'Home', 'url' => route('home')], ['label' => 'Gallery', 'url' => route('gallery.index')], ['label' => $album->title]],
                'schemas' => [StructuredData::imageGallery($album, $url, $photos->map(fn ($photo) => $photo->getFirstMedia('image'))->filter()->take(12))],
            ]),
        ]);
    }
}
